<?php
/**
 * Easy Digital Downloads API
 *
 * A front-facing JSON/XML API that makes it possible to query donation data.
 *
 * @package     EDD
 * @subpackage  Classes/API
 * @copyright   Copyright (c) 2015, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Give_API Class
 *
 * Renders API returns as a JSON/XML array
 *
 * @since  1.1
 */
class Give_API {

	/**
	 * Latest API Version
	 */
	const VERSION = 1;

	/**
	 * Pretty Print?
	 *
	 * @var bool
	 * @access private
	 * @since 1.1
	 */
	private $pretty_print = false;

	/**
	 * Log API requests?
	 *
	 * @var bool
	 * @access private
	 * @since 1.1
	 */
	public $log_requests = true;

	/**
	 * Is this a valid request?
	 *
	 * @var bool
	 * @access private
	 * @since 1.1
	 */
	private $is_valid_request = false;

	/**
	 * User ID Performing the API Request
	 *
	 * @var int
	 * @access private
	 * @since 1.1.1
	 */
	public $user_id = 0;

	/**
	 * Instance of EDD Stats class
	 *
	 * @var object
	 * @access private
	 * @since 1.7
	 */
	private $stats;

	/**
	 * Response data to return
	 *
	 * @var array
	 * @access private
	 * @since 1.1.2
	 */
	private $data = array();

	/**
	 *
	 * @var bool
	 * @access private
	 * @since 1.7
	 */
	public $override = true;

	/**
	 * Version of the API queried
	 *
	 * @var string
	 * @access public
	 * @since 1.1
	 */
	private $queried_version;

	/**
	 * All versions of the API
	 *
	 * @var string
	 * @access public
	 * @since 1.1
	 */
	protected $versions = array();

	/**
	 * Queried endpoint
	 *
	 * @var string
	 * @access public
	 * @since 1.1
	 */
	private $endpoint;

	/**
	 * Endpoints routes
	 *
	 * @var object
	 * @access public
	 * @since 1.1
	 */
	private $routes;

	/**
	 * Setup the EDD API
	 *
	 * @since 1.1
	 */
	public function __construct() {

		$this->versions = array(
			'v1' => 'Give_API_V1',
		);

		foreach( $this->get_versions() as $version => $class ) {
			require_once GIVE_PLUGIN_DIR . 'includes/api/class-give-api-' . $version . '.php';
		}

		add_action( 'init',                     array( $this, 'add_endpoint'     ) );
		add_action( 'template_redirect',        array( $this, 'process_query'    ), -1 );
		add_filter( 'query_vars',               array( $this, 'query_vars'       ) );
		add_action( 'show_user_profile',        array( $this, 'user_key_field'   ) );
		add_action( 'edit_user_profile',        array( $this, 'user_key_field'   ) );
		add_action( 'personal_options_update',  array( $this, 'update_key'       ) );
		add_action( 'edit_user_profile_update', array( $this, 'update_key'       ) );
		add_action( 'give_process_api_key',      array( $this, 'process_api_key'  ) );

		// Setup a backwards compatibilty check for user API Keys
		add_filter( 'get_user_metadata',        array( $this, 'api_key_backwards_copmat' ), 10, 4 );

		// Determine if JSON_PRETTY_PRINT is available
		$this->pretty_print = defined( 'JSON_PRETTY_PRINT' ) ? JSON_PRETTY_PRINT : null;

		// Allow API request logging to be turned off
		$this->log_requests = apply_filters( 'give_api_log_requests', $this->log_requests );

		// Setup EDD_Stats instance
		$this->stats = new EDD_Payment_Stats;

	}

	/**
	 * Registers a new rewrite endpoint for accessing the API
	 *
	 * @access public
	 * @param array $rewrite_rules WordPress Rewrite Rules
	 * @since 1.1
	 */
	public function add_endpoint( $rewrite_rules ) {
		add_rewrite_endpoint( 'give-api', EP_ALL );
	}

	/**
	 * Registers query vars for API access
	 *
	 * @access public
	 * @since 1.1
	 * @param array $vars Query vars
	 * @return string[] $vars New query vars
	 */
	public function query_vars( $vars ) {

		$vars[] = 'token';
		$vars[] = 'key';
		$vars[] = 'query';
		$vars[] = 'type';
		$vars[] = 'product';
		$vars[] = 'number';
		$vars[] = 'date';
		$vars[] = 'startdate';
		$vars[] = 'enddate';
		$vars[] = 'customer';
		$vars[] = 'discount';
		$vars[] = 'format';
		$vars[] = 'id';
		$vars[] = 'purchasekey';
		$vars[] = 'email';

		return $vars;
	}

	/**
	 * Retrieve the API versions
	 *
	 * @access public
	 * @since 1.1
	 * @return array
	 */
	public function get_versions() {
		return $this->versions;
	}

	/**
	 * Retrieve the API version that was queried
	 *
	 * @access public
	 * @since 1.1
	 * @return string
	 */
	public function get_queried_version() {
		return $this->queried_version;
	}

	/**
	 * Retrieves the default version of the API to use
	 *
	 * @access private
	 * @since 1.1
	 * @return string
	 */
	public function get_default_version() {

		$version = get_option( 'give_default_api_version' );

		if( defined( 'GIVE_API_VERSION' ) ) {
			$version = GIVE_API_VERSION;
		} elseif( ! $version ) {
			$version = 'v1';
		}

		return $version;
	}

	/**
	 * Sets the version of the API that was queried.
	 *
	 * Falls back to the default version if no version is specified
	 *
	 * @access private
	 * @since 1.1
	 */
	private function set_queried_version() {

		global $wp_query;

		$version = $wp_query->query_vars['give-api'];

		if( strpos( $version, '/' ) ) {

			$version = explode( '/', $version );
			$version = strtolower( $version[0] );

			$wp_query->query_vars['give-api'] = str_replace( $version . '/', '', $wp_query->query_vars['give-api'] );

			if( array_key_exists( $version, $this->versions ) ) {

				$this->queried_version = $version;

			} else {

				$this->is_valid_request = false;
				$this->invalid_version();
			}

		} else {

			$this->queried_version = $this->get_default_version();

		}

	}

	/**
	 * Validate the API request
	 *
	 * Checks for the user's public key and token against the secret key
	 *
	 * @access private
	 * @global object $wp_query WordPress Query
	 * @uses Give_API::get_user()
	 * @uses Give_API::invalid_key()
	 * @uses Give_API::invalid_auth()
	 * @since 1.1
	 * @return void
	 */
	private function validate_request() {
		global $wp_query;

		$this->override = false;

		// Make sure we have both user and api key
		if ( ! empty( $wp_query->query_vars['give-api'] ) && ( $wp_query->query_vars['give-api'] != 'products' || ! empty( $wp_query->query_vars['token'] ) ) ) {

			if ( empty( $wp_query->query_vars['token'] ) || empty( $wp_query->query_vars['key'] ) ) {
				$this->missing_auth();
			}

			// Auth was provided, include the upgrade routine so we can use the fallback api checks
			require GIVE_PLUGIN_DIR . 'includes/admin/upgrades/upgrade-functions.php';

			// Retrieve the user by public API key and ensure they exist
			if ( ! ( $user = $this->get_user( $wp_query->query_vars['key'] ) ) ) {

				$this->invalid_key();

			} else {

				$token  = urldecode( $wp_query->query_vars['token'] );
				$secret = $this->get_user_secret_key( $user );
				$public = urldecode( $wp_query->query_vars['key'] );

				if ( hash_equals( md5( $secret . $public ), $token ) ) {
					$this->is_valid_request = true;
				} else {
					$this->invalid_auth();
				}
			}
		} elseif ( ! empty( $wp_query->query_vars['give-api'] ) && $wp_query->query_vars['give-api'] == 'products' ) {
			$this->is_valid_request = true;
			$wp_query->set( 'key', 'public' );
		}
	}

	/**
	 * Retrieve the user ID based on the public key provided
	 *
	 * @access public
	 * @since 1.1.1
	 * @global object $wpdb Used to query the database using the WordPress
	 * Database API
	 *
	 * @param string $key Public Key
	 *
	 * @return bool if user ID is found, false otherwise
	 */
	public function get_user( $key = '' ) {
		global $wpdb, $wp_query;

		if( empty( $key ) ) {
			$key = urldecode( $wp_query->query_vars['key'] );
		}

		if ( empty( $key ) ) {
			return false;
		}

		$user = get_transient( md5( 'give_api_user_' . $key ) );

		if ( false === $user ) {
			if ( give_has_upgrade_completed( 'upgrade_user_api_keys' ) ) {
				$user = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s LIMIT 1", $key ) );
			} else {
				$user = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'give_user_public_key' AND meta_value = %s LIMIT 1", $key ) );
			}
			set_transient( md5( 'give_api_user_' . $key ) , $user, DAY_IN_SECONDS );
		}

		if ( $user != NULL ) {
			$this->user_id = $user;
			return $user;
		}

		return false;
	}

	public function get_user_public_key( $user_id = 0 ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			return '';
		}

		$cache_key       = md5( 'give_api_user_public_key' . $user_id );
		$user_public_key = get_transient( $cache_key );

		if ( empty( $user_public_key ) ) {
			if ( give_has_upgrade_completed( 'upgrade_user_api_keys' ) ) {
				$user_public_key = $wpdb->get_var( $wpdb->prepare( "SELECT meta_key FROM $wpdb->usermeta WHERE meta_value = 'give_user_public_key' AND user_id = %d", $user_id ) );
			} else {
				$user_public_key = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wpdb->usermeta WHERE meta_key = 'give_user_public_key' AND user_id = %d", $user_id ) );
			}
			set_transient( $cache_key, $user_public_key, HOUR_IN_SECONDS );
		}

		return $user_public_key;
	}

	public function get_user_secret_key( $user_id = 0 ) {
		global $wpdb;

		if ( empty( $user_id ) ) {
			return '';
		}

		$cache_key       = md5( 'give_api_user_secret_key' . $user_id );
		$user_secret_key = get_transient( $cache_key );

		if ( empty( $user_secret_key ) ) {
			if ( give_has_upgrade_completed( 'upgrade_user_api_keys' ) ) {
				$user_secret_key = $wpdb->get_var( $wpdb->prepare( "SELECT meta_key FROM $wpdb->usermeta WHERE meta_value = 'give_user_secret_key' AND user_id = %d", $user_id ) );
			} else {
				$user_secret_key = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wpdb->usermeta WHERE meta_key = 'give_user_secret_key' AND user_id = %d", $user_id ) );
			}
			set_transient( $cache_key, $user_secret_key, HOUR_IN_SECONDS );
		}

		return $user_secret_key;
	}

	/**
	 * Displays a missing authentication error if all the parameters aren't
	 * provided
	 *
	 * @access private
	 * @uses Give_API::output()
	 * @since 1.1
	 */
	private function missing_auth() {
		$error = array();
		$error['error'] = __( 'You must specify both a token and API key!', 'edd' );

		$this->data = $error;
		$this->output( 401 );
	}

	/**
	 * Displays an authentication failed error if the user failed to provide valid
	 * credentials
	 *
	 * @access private
	 * @since  1.1
	 * @uses Give_API::output()
	 * @return void
	 */
	private function invalid_auth() {
		$error = array();
		$error['error'] = __( 'Your request could not be authenticated!', 'edd' );

		$this->data = $error;
		$this->output( 401 );
	}

	/**
	 * Displays an invalid API key error if the API key provided couldn't be
	 * validated
	 *
	 * @access private
	 * @since 1.1
	 * @uses Give_API::output()
	 * @return void
	 */
	private function invalid_key() {
		$error = array();
		$error['error'] = __( 'Invalid API key!', 'edd' );

		$this->data = $error;
		$this->output( 401 );
	}

	/**
	 * Displays an invalid version error if the version number passed isn't valid
	 *
	 * @access private
	 * @since 1.1
	 * @uses Give_API::output()
	 * @return void
	 */
	private function invalid_version() {
		$error = array();
		$error['error'] = __( 'Invalid API version!', 'edd' );

		$this->data = $error;
		$this->output( 404 );
	}

	/**
	 * Listens for the API and then processes the API requests
	 *
	 * @access public
	 * @global $wp_query
	 * @since 1.1
	 * @return void
	 */
	public function process_query() {

		global $wp_query;

		// Start logging how long the request takes for logging
		$before = microtime( true );

		// Check for give-api var. Get out if not present
		if ( empty( $wp_query->query_vars['give-api'] ) ) {
			return;
		}

		// Determine which version was queried
		$this->set_queried_version();

		// Determine the kind of query
		$this->set_query_mode();

		// Check for a valid user and set errors if necessary
		$this->validate_request();

		// Only proceed if no errors have been noted
		if( ! $this->is_valid_request ) {
			return;
		}

		if( ! defined( 'EDD_DOING_API' ) ) {
			define( 'EDD_DOING_API', true );
		}

		$data = array();
		$this->routes = new $this->versions[ $this->get_queried_version() ];

		switch( $this->endpoint ) :

			case 'stats' :

				$data = $this->routes->get_stats( array(
					'type'      => isset( $wp_query->query_vars['type'] )      ? $wp_query->query_vars['type']      : null,
					'product'   => isset( $wp_query->query_vars['product'] )   ? $wp_query->query_vars['product']   : null,
					'date'      => isset( $wp_query->query_vars['date'] )      ? $wp_query->query_vars['date']      : null,
					'startdate' => isset( $wp_query->query_vars['startdate'] ) ? $wp_query->query_vars['startdate'] : null,
					'enddate'   => isset( $wp_query->query_vars['enddate'] )   ? $wp_query->query_vars['enddate']   : null
				) );

				break;

			case 'products' :

				$product = isset( $wp_query->query_vars['product'] )   ? $wp_query->query_vars['product']   : null;

				$data = $this->routes->get_products( $product );

				break;

			case 'customers' :

				$customer = isset( $wp_query->query_vars['customer'] ) ? $wp_query->query_vars['customer']  : null;

				$data = $this->routes->get_customers( $customer );

				break;

			case 'sales' :

				$data = $this->routes->get_recent_sales();

				break;

			case 'discounts' :

				$discount = isset( $wp_query->query_vars['discount'] ) ? $wp_query->query_vars['discount']  : null;

				$data = $this->routes->get_discounts( $discount );

				break;

		endswitch;

		// Allow extensions to setup their own return data
		$this->data = apply_filters( 'give_api_output_data', $data, $this->endpoint, $this );

		$after        = microtime( true );
		$request_time = ( $after - $before );
		$this->data['request_speed'] = $request_time;

		// Log this API request, if enabled. We log it here because we have access to errors.
		$this->log_request( $this->data );

		// Send out data to the output function
		$this->output();
	}

	/**
	 * Returns the API endpoint requested
	 *
	 * @access private
	 * @since 1.1
	 * @return string $query Query mode
	 */
	public function get_query_mode() {

		return $this->endpoint;
	}

	/**
	 * Determines the kind of query requested and also ensure it is a valid query
	 *
	 * @access private
	 * @since 1.1
	 * @global $wp_query
	 */
	public function set_query_mode() {

		global $wp_query;

		// Whitelist our query options
		$accepted = apply_filters( 'give_api_valid_query_modes', array(
			'stats',
			'products',
			'customers',
			'sales',
			'discounts'
		) );

		$query = isset( $wp_query->query_vars['give-api'] ) ? $wp_query->query_vars['give-api'] : null;
		$query = str_replace( $this->queried_version . '/', '', $query );

		$error = array();

		// Make sure our query is valid
		if ( ! in_array( $query, $accepted ) ) {
			$error['error'] = __( 'Invalid query!', 'edd' );

			$this->data = $error;
			$this->output();
		}

		$this->endpoint = $query;
	}

	/**
	 * Get page number
	 *
	 * @access private
	 * @since 1.1
	 * @global $wp_query
	 * @return int $wp_query->query_vars['page'] if page number returned (default: 1)
	 */
	public function get_paged() {
		global $wp_query;

		return isset( $wp_query->query_vars['page'] ) ? $wp_query->query_vars['page'] : 1;
	}


	/**
	 * Number of results to display per page
	 *
	 * @access private
	 * @since 1.1
	 * @global $wp_query
	 * @return int $per_page Results to display per page (default: 10)
	 */
	public function per_page() {
		global $wp_query;

		$per_page = isset( $wp_query->query_vars['number'] ) ? $wp_query->query_vars['number'] : 10;

		if( $per_page < 0 && $this->get_query_mode() == 'customers' )
			$per_page = 99999999; // Customers query doesn't support -1

		return apply_filters( 'give_api_results_per_page', $per_page );
	}

	/**
	 * Sets up the dates used to retrieve earnings/sales
	 *
	 * @access public
	 * @since 1.1.1
	 * @param array $args Arguments to override defaults
	 * @return array $dates
	*/
	public function get_dates( $args = array() ) {
		$dates = array();

		$defaults = array(
			'type'      => '',
			'product'   => null,
			'date'      => null,
			'startdate' => null,
			'enddate'   => null
		);

		$args = wp_parse_args( $args, $defaults );

		$current_time = current_time( 'timestamp' );

		if ( 'range' === $args['date'] ) {
			$startdate          = strtotime( $args['startdate'] );
			$enddate            = strtotime( $args['enddate'] );
			$dates['day_start'] = date( 'd', $startdate );
			$dates['day_end']   = date( 'd', $enddate );
			$dates['m_start']   = date( 'n', $startdate );
			$dates['m_end']     = date( 'n', $enddate );
			$dates['year']      = date( 'Y', $startdate );
			$dates['year_end'] 	= date( 'Y', $enddate );
		} else {
			// Modify dates based on predefined ranges
			switch ( $args['date'] ) :

				case 'this_month' :
					$dates['day']       = null;
					$dates['m_start']   = date( 'n', $current_time );
					$dates['m_end']     = date( 'n', $current_time );
					$dates['year']      = date( 'Y', $current_time );
				break;

				case 'last_month' :
					$dates['day']     = null;
					$dates['m_start'] = date( 'n', $current_time ) == 1 ? 12 : date( 'n', $current_time ) - 1;
					$dates['m_end']   = $dates['m_start'];
					$dates['year']    = date( 'n', $current_time ) == 1 ? date( 'Y', $current_time ) - 1 : date( 'Y', $current_time );
				break;

				case 'today' :
					$dates['day']       = date( 'd', $current_time );
					$dates['m_start']   = date( 'n', $current_time );
					$dates['m_end']     = date( 'n', $current_time );
					$dates['year']      = date( 'Y', $current_time );
				break;

				case 'yesterday' :

					$year               = date( 'Y', $current_time );
					$month              = date( 'n', $current_time );
					$day                = date( 'd', $current_time );

					if ( $month == 1 && $day == 1 ) {

						$year -= 1;
						$month = 12;
						$day   = cal_days_in_month( CAL_GREGORIAN, $month, $year );

					} elseif ( $month > 1 && $day == 1 ) {

						$month -= 1;
						$day   = cal_days_in_month( CAL_GREGORIAN, $month, $year );

					} else {

						$day -= 1;

					}

					$dates['day']       = $day;
					$dates['m_start']   = $month;
					$dates['m_end']     = $month;
					$dates['year']      = $year;

				break;

				case 'this_quarter' :
					$month_now = date( 'n', $current_time );

					$dates['day']           = null;

					if ( $month_now <= 3 ) {

						$dates['m_start']   = 1;
						$dates['m_end']     = 3;
						$dates['year']      = date( 'Y', $current_time );

					} else if ( $month_now <= 6 ) {

						$dates['m_start']   = 4;
						$dates['m_end']     = 6;
						$dates['year']      = date( 'Y', $current_time );

					} else if ( $month_now <= 9 ) {

						$dates['m_start']   = 7;
						$dates['m_end']     = 9;
						$dates['year']      = date( 'Y', $current_time );

					} else {

						$dates['m_start']   = 10;
						$dates['m_end']     = 12;
						$dates['year']      = date( 'Y', $current_time );

					}
				break;

				case 'last_quarter' :
					$month_now = date( 'n', $current_time );

					$dates['day']           = null;

					if ( $month_now <= 3 ) {

						$dates['m_start']   = 10;
						$dates['m_end']     = 12;
						$dates['year']      = date( 'Y', $current_time ) - 1; // Previous year

					} else if ( $month_now <= 6 ) {

						$dates['m_start']   = 1;
						$dates['m_end']     = 3;
						$dates['year']      = date( 'Y', $current_time );

					} else if ( $month_now <= 9 ) {

						$dates['m_start']   = 4;
						$dates['m_end']     = 6;
						$dates['year']      = date( 'Y', $current_time );

					} else {

						$dates['m_start']   = 7;
						$dates['m_end']     = 9;
						$dates['year']      = date( 'Y', $current_time );

					}
				break;

				case 'this_year' :
					$dates['day']       = null;
					$dates['m_start']   = null;
					$dates['m_end']     = null;
					$dates['year']      = date( 'Y', $current_time );
				break;

				case 'last_year' :
					$dates['day']       = null;
					$dates['m_start']   = null;
					$dates['m_end']     = null;
					$dates['year']      = date( 'Y', $current_time ) - 1;
				break;

			endswitch;
		}

		/**
		 * Returns the filters for the dates used to retreive earnings/sales
		 *
		 * @since 1.1.1
		 * @param object $dates The dates used for retreiving earnings/sales
		 */

		return apply_filters( 'give_api_stat_dates', $dates );
	}

	/**
	 * Process Get Customers API Request
	 *
	 * @access public
	 * @since 1.1
	 * @global object $wpdb Used to query the database using the WordPress
	 *   Database API
	 * @param int $customer Customer ID
	 * @return array $customers Multidimensional array of the customers
	 */
	public function get_customers( $customer = null ) {

		$customers = array();
		$error = array();
		if( ! user_can( $this->user_id, 'view_shop_sensitive_data' ) && ! $this->override ) {
			return $customers;
		}

		global $wpdb;

		$paged    = $this->get_paged();
		$per_page = $this->per_page();
		$offset   = $per_page * ( $paged - 1 );

		if( is_numeric( $customer ) ) {
			$field = 'id';
		} else {
			$field = 'email';
		}

		$customer_query = Give()->customers->get_customers( array( 'number' => $per_page, 'offset' => $offset, $field => $customer ) );
		$customer_count = 0;

		if( $customer_query ) {

			foreach ( $customer_query as $customer_obj ) {

				$names      = explode( ' ', $customer_obj->name );
				$first_name = ! empty( $names[0] ) ? $names[0] : '';
				$last_name  = '';
				if( ! empty( $names[1] ) ) {
					unset( $names[0] );
					$last_name = implode( ' ', $names );
				}

				$customers['customers'][$customer_count]['info']['id']           = '';
				$customers['customers'][$customer_count]['info']['user_id']      = '';
				$customers['customers'][$customer_count]['info']['username']     = '';
				$customers['customers'][$customer_count]['info']['display_name'] = '';
				$customers['customers'][$customer_count]['info']['customer_id']  = $customer_obj->id;
				$customers['customers'][$customer_count]['info']['first_name']   = $first_name;
				$customers['customers'][$customer_count]['info']['last_name']    = $last_name;
				$customers['customers'][$customer_count]['info']['email']        = $customer_obj->email;

				if ( ! empty( $customer_obj->user_id ) ) {

					$user_data = get_userdata( $customer_obj->user_id );

					// Customer with registered account

					// id is going to get deprecated in the future, user user_id or customer_id instead
					$customers['customers'][$customer_count]['info']['id']           = $customer_obj->user_id;
					$customers['customers'][$customer_count]['info']['user_id']      = $customer_obj->user_id;
					$customers['customers'][$customer_count]['info']['username']     = $user_data->user_login;
					$customers['customers'][$customer_count]['info']['display_name'] = $user_data->display_name;

				}

				$customers['customers'][$customer_count]['stats']['total_purchases'] = $customer_obj->purchase_count;
				$customers['customers'][$customer_count]['stats']['total_spent']     = $customer_obj->purchase_value;
				$customers['customers'][$customer_count]['stats']['total_downloads'] = give_count_file_downloads_of_user( $customer_obj->email );

				$customer_count++;

			}

		} elseif( $customer ) {

			$error['error'] = sprintf( __( 'Customer %s not found!', 'edd' ), $customer );
			return $error;

		} else {

			$error['error'] = __( 'No customers found!', 'edd' );
			return $error;

		}

		return $customers;
	}

	/**
	 * Process Get Products API Request
	 *
	 * @access public
	 * @since 1.1
	 * @param int $product Product (Download) ID
	 * @return array $customers Multidimensional array of the products
	 */
	public function get_products( $product = null ) {

		$products = array();
		$error = array();

		if ( $product == null ) {
			$products['products'] = array();

			$product_list = get_posts( array(
				'post_type'        => 'download',
				'posts_per_page'   => $this->per_page(),
				'suppress_filters' => true,
				'paged'            => $this->get_paged()
			) );

			if ( $product_list ) {
				$i = 0;
				foreach ( $product_list as $product_info ) {
					$products['products'][$i] = $this->get_product_data( $product_info );
					$i++;
				}
			}
		} else {
			if ( get_post_type( $product ) == 'download' ) {
				$product_info = get_post( $product );

				$products['products'][0] = $this->get_product_data( $product_info );

			} else {
				$error['error'] = sprintf( __( 'Product %s not found!', 'edd' ), $product );
				return $error;
			}
		}

		return $products;
	}

	/**
	 * Given a download post object, generate the data for the API output
	 *
	 * @since  2.3.9
	 * @param  object $product_info The Download Post Object
	 * @return array                Array of post data to return back in the API
	 */
	private function get_product_data( $product_info ) {

		$product = array();

		$product['info']['id']                           = $product_info->ID;
		$product['info']['slug']                         = $product_info->post_name;
		$product['info']['title']                        = $product_info->post_title;
		$product['info']['create_date']                  = $product_info->post_date;
		$product['info']['modified_date']                = $product_info->post_modified;
		$product['info']['status']                       = $product_info->post_status;
		$product['info']['link']                         = html_entity_decode( $product_info->guid );
		$product['info']['content']                      = $product_info->post_content;
		$product['info']['thumbnail']                    = wp_get_attachment_url( get_post_thumbnail_id( $product_info->ID ) );
		$product['info']['category']                     = get_the_terms( $product_info, 'download_category' );
		$product['info']['tags']                         = get_the_terms( $product_info, 'download_tag' );

		if( user_can( $this->user_id, 'view_shop_reports' ) || $this->override ) {
			$product['stats']['total']['sales']              = give_get_download_sales_stats( $product_info->ID );
			$product['stats']['total']['earnings']           = give_get_download_earnings_stats( $product_info->ID );
			$product['stats']['monthly_average']['sales']    = give_get_average_monthly_download_sales( $product_info->ID );
			$product['stats']['monthly_average']['earnings'] = give_get_average_monthly_download_earnings( $product_info->ID );
		}

		if ( give_has_variable_prices( $product_info->ID ) ) {
			foreach ( give_get_variable_prices( $product_info->ID ) as $price ) {
				$product['pricing'][ sanitize_key( $price['name'] ) ] = $price['amount'];
			}
		} else {
			$product['pricing']['amount'] = give_get_download_price( $product_info->ID );
		}

		if( user_can( $this->user_id, 'view_shop_sensitive_data' ) || $this->override ) {
			foreach ( give_get_download_files( $product_info->ID ) as $file ) {
				$product['files'][] = $file;
			}
			$product['notes'] = give_get_product_notes( $product_info->ID );
		}

		return apply_filters( 'give_api_products_product', $product );

	}

	/**
	 * Process Get Stats API Request
	 *
	 * @since 1.1
	 *
	 * @global object $wpdb Used to query the database using the WordPress
	 *
	 * @param array $args Arguments provided by API Request
	 *
	 * @return array
	 */
	public function get_stats( $args = array() ) {
		$defaults = array(
			'type'      => null,
			'product'   => null,
			'date'      => null,
			'startdate' => null,
			'enddate'   => null
		);

		$args = wp_parse_args( $args, $defaults );

		$dates = $this->get_dates( $args );

		$stats    = array();
		$earnings = array(
			'earnings' => array()
		);
		$sales    = array(
			'sales' => array()
		);
		$error    = array();

		if( ! user_can( $this->user_id, 'view_shop_reports' ) && ! $this->override ) {
			return $stats;
		}

		if ( $args['type'] == 'sales' ) {
			if ( $args['product'] == null ) {
				if ( $args['date'] == null ) {
					$sales = $this->get_default_sales_stats();
				} elseif( $args['date'] === 'range' ) {
					// Return sales for a date range

					// Ensure the end date is later than the start date
					if( $args['enddate'] < $args['startdate'] ) {
						$error['error'] = __( 'The end date must be later than the start date!', 'edd' );
					}

					// Ensure both the start and end date are specified
					if ( empty( $args['startdate'] ) || empty( $args['enddate'] ) ) {
						$error['error'] = __( 'Invalid or no date range specified!', 'edd' );
					}

					$total = 0;

					// Loop through the years
					$y = $dates['year'];
					while( $y <= $dates['year_end'] ) :

						if( $dates['year'] == $dates['year_end'] ) {
							$month_start = $dates['m_start'];
							$month_end   = $dates['m_end'];
						} elseif( $y == $dates['year'] && $dates['year_end'] > $dates['year'] ) {
							$month_start = $dates['m_start'];
							$month_end   = 12;
						} elseif( $y == $dates['year_end'] ) {
							$month_start = 1;
							$month_end   = $dates['m_end'];
						} else {
							$month_start = 1;
							$month_end   = 12;
						}

						$i = $month_start;
						while ( $i <= $month_end ) :

							if( $i == $dates['m_start'] ) {
								$d = $dates['day_start'];
							} else {
								$d = 1;
							}

							if( $i == $dates['m_end'] ) {
								$num_of_days = $dates['day_end'];
							} else {
								$num_of_days 	= cal_days_in_month( CAL_GREGORIAN, $i, $y );
							}

							while ( $d <= $num_of_days ) :
								$sale_count = give_get_sales_by_date( $d, $i, $y );
								$date_key   = date( 'Ymd', strtotime( $y . '/' . $i . '/' . $d ) );
								if ( ! isset( $sales['sales'][ $date_key ] ) ) {
									$sales['sales'][ $date_key ] = 0;
								}
								$sales['sales'][ $date_key ] += $sale_count;
								$total += $sale_count;
								$d++;
							endwhile;
							$i++;
						endwhile;

						$y++;
					endwhile;

					$sales['totals'] = $total;
				} else {
					if( $args['date'] == 'this_quarter' || $args['date'] == 'last_quarter'  ) {
						$sales_count = 0;

						// Loop through the months
						$month = $dates['m_start'];

						while( $month <= $dates['m_end'] ) :
							$sales_count += give_get_sales_by_date( null, $month, $dates['year'] );
							$month++;
						endwhile;

						$sales['sales'][ $args['date'] ] = $sales_count;
					} else {
						$sales['sales'][ $args['date'] ] = give_get_sales_by_date( $dates['day'], $dates['m_start'], $dates['year'] );
					}
				}
			} elseif ( $args['product'] == 'all' ) {
				$products = get_posts( array( 'post_type' => 'download', 'nopaging' => true ) );
				$i = 0;
				foreach ( $products as $product_info ) {
					$sales['sales'][$i] = array( $product_info->post_name => give_get_download_sales_stats( $product_info->ID ) );
					$i++;
				}
			} else {
				if ( get_post_type( $args['product'] ) == 'download' ) {
					$product_info = get_post( $args['product'] );
					$sales['sales'][0] = array( $product_info->post_name => give_get_download_sales_stats( $args['product'] ) );
				} else {
					$error['error'] = sprintf( __( 'Product %s not found!', 'edd' ), $args['product'] );
				}
			}

			if ( ! empty( $error ) )
				return $error;

			return $sales;
		} elseif ( $args['type'] == 'earnings' ) {
			if ( $args['product'] == null ) {
				if ( $args['date'] == null ) {
					$earnings = $this->get_default_earnings_stats();
				} elseif ( $args['date'] === 'range' ) {
					// Return sales for a date range

					// Ensure the end date is later than the start date
					if ( $args['enddate'] < $args['startdate'] ) {
						$error['error'] = __( 'The end date must be later than the start date!', 'edd' );
					}

					// Ensure both the start and end date are specified
					if ( empty( $args['startdate'] ) || empty( $args['enddate'] ) ) {
						$error['error'] = __( 'Invalid or no date range specified!', 'edd' );
					}

					$total = (float) 0.00;

					// Loop through the years
					$y = $dates['year'];
					if ( ! isset( $earnings['earnings'] ) ) {
						$earnings['earnings'] = array();
					}
					while( $y <= $dates['year_end'] ) :

						if( $dates['year'] == $dates['year_end'] ) {
							$month_start = $dates['m_start'];
							$month_end   = $dates['m_end'];
						} elseif( $y == $dates['year'] && $dates['year_end'] > $dates['year'] ) {
							$month_start = $dates['m_start'];
							$month_end   = 12;
						} elseif( $y == $dates['year_end'] ) {
							$month_start = 1;
							$month_end   = $dates['m_end'];
						} else {
							$month_start = 1;
							$month_end   = 12;
						}

						$i = $month_start;
						while ( $i <= $month_end ) :

							if( $i == $dates['m_start'] )
								$d = $dates['day_start'];
							else
								$d = 1;

							if( $i == $dates['m_end'] ) {
								$num_of_days = $dates['day_end'];
							} else {
								$num_of_days = cal_days_in_month( CAL_GREGORIAN, $i, $y );
							}

							while ( $d <= $num_of_days ) :
								$earnings_stat = give_get_earnings_by_date( $d, $i, $y );
								$date_key = date( 'Ymd', strtotime( $y . '/' . $i . '/' . $d ) );
								if ( ! isset( $earnings['earnings'][ $date_key ] ) ) {
									$earnings['earnings'][ $date_key ] = 0;
								}
								$earnings['earnings'][ $date_key ] += $earnings_stat;
								$total += $earnings_stat;
								$d++;
							endwhile;

							$i++;
						endwhile;

						$y++;
					endwhile;

					$earnings['totals'] = $total;
				} else {
					if ( $args['date'] == 'this_quarter' || $args['date'] == 'last_quarter'  ) {
						$earnings_count = (float) 0.00;

						// Loop through the months
						$month = $dates['m_start'];

						while ( $month <= $dates['m_end'] ) :
							$earnings_count += give_get_earnings_by_date( null, $month, $dates['year'] );
							$month++;
						endwhile;

						$earnings['earnings'][ $args['date'] ] = $earnings_count;
					} else {
						$earnings['earnings'][ $args['date'] ] = give_get_earnings_by_date( $dates['day'], $dates['m_start'], $dates['year'] );
					}
				}
			} elseif ( $args['product'] == 'all' ) {
				$products = get_posts( array( 'post_type' => 'download', 'nopaging' => true ) );

				$i = 0;
				foreach ( $products as $product_info ) {
					$earnings['earnings'][ $i ] = array( $product_info->post_name => give_get_download_earnings_stats( $product_info->ID ) );
					$i++;
				}
			} else {
				if ( get_post_type( $args['product'] ) == 'download' ) {
					$product_info = get_post( $args['product'] );
					$earnings['earnings'][0] = array( $product_info->post_name => give_get_download_earnings_stats( $args['product'] ) );
				} else {
					$error['error'] = sprintf( __( 'Product %s not found!', 'edd' ), $args['product'] );
				}
			}

			if ( ! empty( $error ) )
				return $error;

			return $earnings;
		} elseif ( $args['type'] == 'customers' ) {
			if ( version_compare( $give_version, '2.3', '<' ) || ! give_has_upgrade_completed( 'upgrade_customer_payments_association' ) ) {
				global $wpdb;
				$stats = array();
				$count = $wpdb->get_col( "SELECT COUNT(DISTINCT meta_value) FROM $wpdb->postmeta WHERE meta_key = '_give_payment_user_email'" );
				$stats['customers']['total_customers'] = $count[0];
				return $stats;
			} else {
				$customers = new EDD_DB_Customers();
				$stats['customers']['total_customers'] = $customers->count();
				return $stats;
			}
		} elseif ( empty( $args['type'] ) ) {
			$stats = array_merge( $stats, $this->get_default_sales_stats() );
			$stats = array_merge ( $stats, $this->get_default_earnings_stats() );

			return array( 'stats' => $stats );
		}
	}

	/**
	 * Retrieves Recent Sales
	 *
	 * @access public
	 * @since  1.1
	 * @return array
	 */
	public function get_recent_sales() {
		global $wp_query;

		$sales = array();

		if( ! user_can( $this->user_id, 'view_shop_reports' ) && ! $this->override ) {
			return $sales;
		}

		if( isset( $wp_query->query_vars['id'] ) ) {
			$query   = array();
			$query[] = give_get_payment_by( 'id', $wp_query->query_vars['id'] );
		} elseif( isset( $wp_query->query_vars['purchasekey'] ) ) {
			$query   = array();
			$query[] = give_get_payment_by( 'key', $wp_query->query_vars['purchasekey'] );
		} elseif( isset( $wp_query->query_vars['email'] ) ) {
			$query = give_get_payments( array( 'meta_key' => '_give_payment_user_email', 'meta_value' => $wp_query->query_vars['email'], 'number' => $this->per_page(), 'page' => $this->get_paged(), 'status' => 'publish' ) );
		} else {
			$query = give_get_payments( array( 'number' => $this->per_page(), 'page' => $this->get_paged(), 'status' => 'publish' ) );
		}

		if ( $query ) {
			$i = 0;
			foreach ( $query as $payment ) {
				$payment_meta = give_get_payment_meta( $payment->ID );
				$user_info    = give_get_payment_meta_user_info( $payment->ID );
				$cart_items   = give_get_payment_meta_cart_details( $payment->ID );

				$sales['sales'][ $i ]['ID']             = give_get_payment_number( $payment->ID );
				$sales['sales'][ $i ]['transaction_id'] = give_get_payment_transaction_id( $payment->ID );
				$sales['sales'][ $i ]['key']            = give_get_payment_key( $payment->ID );
				$sales['sales'][ $i ]['discount']       = isset( $user_info['discount'] ) && $user_info['discount'] != 'none' ? explode( ',', $user_info['discount'] ) : array();
				$sales['sales'][ $i ]['subtotal']       = give_get_payment_subtotal( $payment->ID );
				$sales['sales'][ $i ]['tax']            = give_get_payment_tax( $payment->ID );
				$sales['sales'][ $i ]['fees']           = give_get_payment_fees( $payment->ID );
				$sales['sales'][ $i ]['total']          = give_get_payment_amount( $payment->ID );
				$sales['sales'][ $i ]['gateway']        = give_get_payment_gateway( $payment->ID );
				$sales['sales'][ $i ]['email']          = give_get_payment_user_email( $payment->ID );
				$sales['sales'][ $i ]['date']           = $payment->post_date;
				$sales['sales'][ $i ]['products']       = array();

				$c = 0;

				foreach ( $cart_items as $key => $item ) {

					$item_id  = isset( $item['id']    ) ? $item['id']    : $item;
					$price    = isset( $item['price'] ) ? $item['price'] : false;
					$price_id = isset( $item['item_number']['options']['price_id'] ) ? $item['item_number']['options']['price_id'] : null;
					$quantity = isset( $item['quantity'] ) && $item['quantity'] > 0 ? $item['quantity'] : 1;

					if( ! $price ) {
						// This function is only used on payments with near 1.0 cart data structure
						$price = give_get_download_final_price( $item_id, $user_info, null );
					}

					$price_name = '';
					if ( isset( $item['item_number'] ) && isset( $item['item_number']['options'] ) ) {
						$price_options  = $item['item_number']['options'];
						if ( isset( $price_options['price_id'] ) ) {
							$price_name = give_get_price_option_name( $item['id'], $price_options['price_id'], $payment->ID );
						}
					}

					$sales['sales'][ $i ]['products'][ $c ]['quantity']   = $quantity;
					$sales['sales'][ $i ]['products'][ $c ]['name']       = get_the_title( $item['id'] );
					$sales['sales'][ $i ]['products'][ $c ]['price']      = $price;
					$sales['sales'][ $i ]['products'][ $c ]['price_name'] = $price_name;
					$c++;
				}

				$i++;
			}
		}
		return $sales;
	}

	/**
	 * Process Get Discounts API Request
	 *
	 * @access public
	 * @since 1.6
	 * @global object $wpdb Used to query the database using the WordPress
	 *   Database API
	 * @param int $discount Discount ID
	 * @return array $discounts Multidimensional array of the discounts
	 */
	public function get_discounts( $discount = null ) {

		$discount_list = array();

		if( ! user_can( $this->user_id, 'manage_shop_discounts' ) && ! $this->override ) {
			return $discount_list;
		}
		$error = array();

		if ( empty( $discount ) ) {

			global $wpdb;

			$paged     = $this->get_paged();
			$per_page  = $this->per_page();
			$discounts = give_get_discounts( array( 'posts_per_page' => $per_page, 'paged' => $paged ) );
			$count     = 0;

			if ( empty( $discounts ) ) {
				$error['error'] = __( 'No discounts found!', 'edd' );
				return $error;
			}

			foreach ( $discounts as $discount ) {

				$discount_list['discounts'][$count]['ID']                    = $discount->ID;
				$discount_list['discounts'][$count]['name']                  = $discount->post_title;
				$discount_list['discounts'][$count]['code']                  = give_get_discount_code( $discount->ID );
				$discount_list['discounts'][$count]['amount']                = give_get_discount_amount( $discount->ID );
				$discount_list['discounts'][$count]['min_price']             = give_get_discount_min_price( $discount->ID );
				$discount_list['discounts'][$count]['type']                  = give_get_discount_type( $discount->ID );
				$discount_list['discounts'][$count]['uses']                  = give_get_discount_uses( $discount->ID );
				$discount_list['discounts'][$count]['max_uses']              = give_get_discount_max_uses( $discount->ID );
				$discount_list['discounts'][$count]['start_date']            = give_get_discount_start_date( $discount->ID );
				$discount_list['discounts'][$count]['exp_date']              = give_get_discount_expiration( $discount->ID );
				$discount_list['discounts'][$count]['status']                = $discount->post_status;
				$discount_list['discounts'][$count]['product_requirements']  = give_get_discount_product_reqs( $discount->ID );
				$discount_list['discounts'][$count]['requirement_condition'] = give_get_discount_product_condition( $discount->ID );
				$discount_list['discounts'][$count]['global_discount']       = give_is_discount_not_global( $discount->ID );
				$discount_list['discounts'][$count]['single_use']            = give_discount_is_single_use( $discount->ID );

				$count++;
			}

		} else {

			if ( is_numeric( $discount ) && get_post( $discount ) ) {

				$discount_list['discounts'][0]['ID']                         = $discount;
				$discount_list['discounts'][0]['name']                       = get_post_field( 'post_title', $discount );
				$discount_list['discounts'][0]['code']                       = give_get_discount_code( $discount );
				$discount_list['discounts'][0]['amount']                     = give_get_discount_amount( $discount );
				$discount_list['discounts'][0]['min_price']                  = give_get_discount_min_price( $discount );
				$discount_list['discounts'][0]['type']                       = give_get_discount_type( $discount );
				$discount_list['discounts'][0]['uses']                       = give_get_discount_uses( $discount );
				$discount_list['discounts'][0]['max_uses']                   = give_get_discount_max_uses( $discount );
				$discount_list['discounts'][0]['start_date']                 = give_get_discount_start_date( $discount );
				$discount_list['discounts'][0]['exp_date']                   = give_get_discount_expiration( $discount );
				$discount_list['discounts'][0]['status']                     = get_post_field( 'post_status', $discount );
				$discount_list['discounts'][0]['product_requirements']       = give_get_discount_product_reqs( $discount );
				$discount_list['discounts'][0]['requirement_condition']      = give_get_discount_product_condition( $discount );
				$discount_list['discounts'][0]['global_discount']            = give_is_discount_not_global( $discount );
				$discount_list['discounts'][0]['single_use']                 = give_discount_is_single_use( $discount );

			} else {

				$error['error'] = sprintf( __( 'Discount %s not found!', 'edd' ), $discount );
				return $error;

			}

		}

		return $discount_list;
	}

	/**
	 * Retrieve the output format
	 *
	 * Determines whether results should be displayed in XML or JSON
	 *
	 * @since 1.1
	 *
	 * @return mixed|void
	 */
	public function get_output_format() {
		global $wp_query;

		$format = isset( $wp_query->query_vars['format'] ) ? $wp_query->query_vars['format'] : 'json';

		return apply_filters( 'give_api_output_format', $format );
	}


	/**
	 * Log each API request, if enabled
	 *
	 * @access private
	 * @since  1.1
	 * @global $give_logs
	 * @global $wp_query
	 * @param array $data
	 * @return void
	 */
	private function log_request( $data = array() ) {
		if ( ! $this->log_requests )
			return;

		global $give_logs, $wp_query;

		$query = array(
			'give-api'     => $wp_query->query_vars['give-api'],
			'key'         => isset( $wp_query->query_vars['key'] )         ? $wp_query->query_vars['key']         : null,
			'token'       => isset( $wp_query->query_vars['token'] )       ? $wp_query->query_vars['token']       : null,
			'query'       => isset( $wp_query->query_vars['query'] )       ? $wp_query->query_vars['query']       : null,
			'type'        => isset( $wp_query->query_vars['type'] )        ? $wp_query->query_vars['type']        : null,
			'product'     => isset( $wp_query->query_vars['product'] )     ? $wp_query->query_vars['product']     : null,
			'customer'    => isset( $wp_query->query_vars['customer'] )    ? $wp_query->query_vars['customer']    : null,
			'date'        => isset( $wp_query->query_vars['date'] )        ? $wp_query->query_vars['date']        : null,
			'startdate'   => isset( $wp_query->query_vars['startdate'] )   ? $wp_query->query_vars['startdate']   : null,
			'enddate'     => isset( $wp_query->query_vars['enddate'] )     ? $wp_query->query_vars['enddate']     : null,
			'id'          => isset( $wp_query->query_vars['id'] )          ? $wp_query->query_vars['id']          : null,
			'purchasekey' => isset( $wp_query->query_vars['purchasekey'] ) ? $wp_query->query_vars['purchasekey'] : null,
			'email'       => isset( $wp_query->query_vars['email'] )       ? $wp_query->query_vars['email']       : null,
		);

		$log_data = array(
			'log_type'     => 'api_request',
			'post_excerpt' => http_build_query( $query ),
			'post_content' => ! empty( $data['error'] ) ? $data['error'] : '',
		);

		$log_meta = array(
			'request_ip' => give_get_ip(),
			'user'       => $this->user_id,
			'key'        => isset( $wp_query->query_vars['key'] ) ? $wp_query->query_vars['key'] : null,
			'token'      => isset( $wp_query->query_vars['token'] ) ? $wp_query->query_vars['token'] : null,
			'time'       => $data['request_speed'],
			'version'    => $this->get_queried_version()
		);

		$give_logs->insert_log( $log_data, $log_meta );
	}


	/**
	 * Retrieve the output data
	 *
	 * @access public
	 * @since 1.1.2
	 * @return array
	 */
	public function get_output() {
		return $this->data;
	}

	/**
	 * Output Query in either JSON/XML. The query data is outputted as JSON
	 * by default
	 *
	 * @since 1.1
	 * @global $wp_query
	 *
	 * @param int $status_code
	 */
	public function output( $status_code = 200 ) {
		global $wp_query;

		$format = $this->get_output_format();

		status_header( $status_code );

		do_action( 'give_api_output_before', $this->data, $this, $format );

		switch ( $format ) :

			case 'xml' :

				require_once GIVE_PLUGIN_DIR . 'includes/libraries/array2xml.php';
				$xml = Array2XML::createXML( 'edd', $this->data );
				echo $xml->saveXML();

				break;

			case 'json' :

				header( 'Content-Type: application/json' );
				if ( ! empty( $this->pretty_print ) )
					echo json_encode( $this->data, $this->pretty_print );
				else
					echo json_encode( $this->data );

				break;


			default :

				// Allow other formats to be added via extensions
				do_action( 'give_api_output_' . $format, $this->data, $this );

				break;

		endswitch;

		do_action( 'give_api_output_after', $this->data, $this, $format );

		give_die();
	}

	/**
	 * Modify User Profile
	 *
	 * Modifies the output of profile.php to add key generation/revocation
	 *
	 * @access public
	 * @since 1.1
	 * @param object $user Current user info
	 * @return void
	 */
	function user_key_field( $user ) {
		if ( ( give_get_option( 'api_allow_user_keys', false ) || current_user_can( 'manage_shop_settings' ) ) && current_user_can( 'edit_user', $user->ID ) ) {
			$user = get_userdata( $user->ID );
			?>
			<table class="form-table">
				<tbody>
					<tr>
						<th>
							<?php _e( 'Easy Digital Downloads API Keys', 'edd' ); ?>
						</th>
						<td>
							<?php
								$public_key = $this->get_user_public_key( $user->ID );
								$secret_key = $this->get_user_secret_key( $user->ID );
							?>
							<?php if ( empty( $user->give_user_public_key ) ) { ?>
								<input name="give_set_api_key" type="checkbox" id="give_set_api_key" value="0" />
								<span class="description"><?php _e( 'Generate API Key', 'edd' ); ?></span>
							<?php } else { ?>
								<strong style="display:inline-block; width: 125px;"><?php _e( 'Public key:', 'edd' ); ?>&nbsp;</strong><input type="text" disabled="disabled" class="regular-text" id="publickey" value="<?php echo esc_attr( $public_key ); ?>"/><br/>
								<strong style="display:inline-block; width: 125px;"><?php _e( 'Secret key:', 'edd' ); ?>&nbsp;</strong><input type="text" disabled="disabled" class="regular-text" id="privatekey" value="<?php echo esc_attr( $secret_key ); ?>"/><br/>
								<strong style="display:inline-block; width: 125px;"><?php _e( 'Token:', 'edd' ); ?>&nbsp;</strong><input type="text" disabled="disabled" class="regular-text" id="token" value="<?php echo esc_attr( $this->get_token( $user->ID ) ); ?>"/><br/>
								<input name="give_set_api_key" type="checkbox" id="give_set_api_key" value="0" />
								<span class="description"><label for="give_set_api_key"><?php _e( 'Revoke API Keys', 'edd' ); ?></label></span>
							<?php } ?>
						</td>
					</tr>
				</tbody>
			</table>
		<?php }
	}

	/**
	 * Process an API key generation/revocation
	 *
	 * @access public
	 * @since 2.0.0
	 * @param array $args
	 * @return void
	 */
	public function process_api_key( $args ) {

		if( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'give-api-nonce' ) ) {

			wp_die( __( 'Nonce verification failed', 'edd' ), __( 'Error', 'edd' ), array( 'response' => 403 ) );

		}

		if( is_numeric( $args['user_id'] ) ) {
			$user_id    = isset( $args['user_id'] ) ? absint( $args['user_id'] ) : get_current_user_id();
		} else {
			$userdata   = get_user_by( 'login', $args['user_id'] );
			$user_id    = $userdata->ID;
		}
		$process    = isset( $args['give_api_process'] ) ? strtolower( $args['give_api_process'] ) : false;

		if( $user_id == get_current_user_id() && ! give_get_option( 'allow_user_api_keys' ) && ! current_user_can( 'manage_shop_settings' ) ) {
			wp_die( sprintf( __( 'You do not have permission to %s API keys for this user', 'edd' ), $process ), __( 'Error', 'edd' ), array( 'response' => 403 ) );
		} elseif( ! current_user_can( 'manage_shop_settings' ) ) {
			wp_die( sprintf( __( 'You do not have permission to %s API keys for this user', 'edd' ), $process ), __( 'Error', 'edd' ), array( 'response' => 403 ) );
		}

		switch( $process ) {
			case 'generate':
				if( $this->generate_api_key( $user_id ) ) {
					delete_transient( 'give-total-api-keys' );
					wp_redirect( add_query_arg( 'give-message', 'api-key-generated', 'edit.php?post_type=download&page=give-tools&tab=api_keys' ) ); exit();
				} else {
					wp_redirect( add_query_arg( 'give-message', 'api-key-failed', 'edit.php?post_type=download&page=give-tools&tab=api_keys' ) ); exit();
				}
				break;
			case 'regenerate':
				$this->generate_api_key( $user_id, true );
				delete_transient( 'give-total-api-keys' );
				wp_redirect( add_query_arg( 'give-message', 'api-key-regenerated', 'edit.php?post_type=download&page=give-tools&tab=api_keys' ) ); exit();
				break;
			case 'revoke':
				$this->revoke_api_key( $user_id );
				delete_transient( 'give-total-api-keys' );
				wp_redirect( add_query_arg( 'give-message', 'api-key-revoked', 'edit.php?post_type=download&page=give-tools&tab=api_keys' ) ); exit();
				break;
			default;
				break;
		}
	}

	/**
	 * Generate new API keys for a user
	 *
	 * @access public
	 * @since 2.0.0
	 * @param int $user_id User ID the key is being generated for
	 * @param boolean $regenerate Regenerate the key for the user
	 * @return boolean True if (re)generated succesfully, false otherwise.
	 */
	public function generate_api_key( $user_id = 0, $regenerate = false ) {

		if( empty( $user_id ) ) {
			return false;
		}

		$user = get_userdata( $user_id );

		if( ! $user ) {
			return false;
		}

		$public_key = $this->get_user_public_key( $user_id );
		$secret_key = $this->get_user_secret_key( $user_id );

		if ( empty( $public_key ) || $regenerate == true ) {
			$new_public_key = $this->generate_public_key( $user->user_email );
			$new_secret_key = $this->generate_private_key( $user->ID );
		} else {
			return false;
		}

		if ( $regenerate == true ) {
			$this->revoke_api_key( $user->ID );
		}

		update_user_meta( $user_id, $new_public_key, 'give_user_public_key' );
		update_user_meta( $user_id, $new_secret_key, 'give_user_secret_key' );

		return true;
	}

	/**
	 * Revoke a users API keys
	 *
	 * @access public
	 * @since 2.0.0
	 * @param int $user_id User ID of user to revoke key for
	 * @return string
	 */
	public function revoke_api_key( $user_id = 0 ) {

		if( empty( $user_id ) ) {
			return false;
		}

		$user = get_userdata( $user_id );

		if( ! $user ) {
			return false;
		}

		$public_key = $this->get_user_public_key( $user_id );
		$secret_key = $this->get_user_secret_key( $user_id );
		if ( ! empty( $public_key ) ) {
			delete_transient( md5( 'give_api_user_' . $public_key ) );
			delete_transient( md5('give_api_user_public_key' . $user_id ) );
			delete_transient( md5('give_api_user_secret_key' . $user_id ) );
			delete_user_meta( $user_id, $public_key );
			delete_user_meta( $user_id, $secret_key );
		} else {
			return false;
		}

		return true;
	}

	public function get_version() {
		return self::VERSION;
	}


	/**
	 * Generate and Save API key
	 *
	 * Generates the key requested by user_key_field and stores it in the database
	 *
	 * @access public
	 * @since 1.1
	 * @param int $user_id
	 * @return void
	 */
	public function update_key( $user_id ) {
		if ( current_user_can( 'edit_user', $user_id ) && isset( $_POST['give_set_api_key'] ) ) {

			$user = get_userdata( $user_id );

			$public_key = $this->get_user_public_key( $user_id );
			$secret_key = $this->get_user_secret_key( $user_id );

			if ( empty( $public_key ) ) {
				$new_public_key = $this->generate_public_key( $user->user_email );
				$new_secret_key = $this->generate_private_key( $user->ID );

				update_user_meta( $user_id, $new_public_key, 'give_user_public_key' );
				update_user_meta( $user_id, $new_secret_key, 'give_user_secret_key' );
			} else {
				$this->revoke_api_key( $user_id );
			}
		}
	}

	/**
	 * Generate the public key for a user
	 *
	 * @access private
	 * @since 1.1
	 * @param string $user_email
	 * @return string
	 */
	private function generate_public_key( $user_email = '' ) {
		$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
		$public   = hash( 'md5', $user_email . $auth_key . date( 'U' ) );
		return $public;
	}

	/**
	 * Generate the secret key for a user
	 *
	 * @access private
	 * @since 1.1
	 * @param int $user_id
	 * @return string
	 */
	private function generate_private_key( $user_id = 0 ) {
		$auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
		$secret   = hash( 'md5', $user_id . $auth_key . date( 'U' ) );
		return $secret;
	}

	/**
	 * Retrieve the user's token
	 *
	 * @access private
	 * @since 1.1
	 * @param int $user_id
	 * @return string
	 */
	public function get_token( $user_id = 0 ) {
		return hash( 'md5', $this->get_user_secret_key( $user_id ) . $this->get_user_public_key( $user_id ) );
	}

	/**
	 * Generate the default sales stats returned by the 'stats' endpoint
	 *
	 * @access private
	 * @since 1.1
	 * @return array default sales statistics
	 */
	private function get_default_sales_stats() {

		// Default sales return
		$sales = array();
		$sales['sales']['today']         = $this->stats->get_sales( 0, 'today' );
		$sales['sales']['current_month'] = $this->stats->get_sales( 0, 'this_month' );
		$sales['sales']['last_month']    = $this->stats->get_sales( 0, 'last_month' );
		$sales['sales']['totals']        = give_get_total_sales();

		return $sales;
	}

	/**
	 * Generate the default earnings stats returned by the 'stats' endpoint
	 *
	 * @access private
	 * @since 1.1
	 * @return array default earnings statistics
	 */
	private function get_default_earnings_stats() {

		// Default earnings return
		$earnings = array();
		$earnings['earnings']['today']         = $this->stats->get_earnings( 0, 'today' );
		$earnings['earnings']['current_month'] = $this->stats->get_earnings( 0, 'this_month' );
		$earnings['earnings']['last_month']    = $this->stats->get_earnings( 0, 'last_month' );
		$earnings['earnings']['totals']        = give_get_total_earnings();

		return $earnings;
	}

	/**
	 * A Backwards Compatibility call for the change of meta_key/value for users API Keys
	 *
	 * @since  1.1
	 * @param  string $check     Wether to check the cache or not
	 * @param  int $object_id    The User ID being passed
	 * @param  string $meta_key  The user meta key
	 * @param  bool $single      If it should return a single value or array
	 * @return string            The API key/secret for the user supplied
	 */
	public function api_key_backwards_copmat( $check, $object_id, $meta_key, $single ) {

		if ( $meta_key !== 'give_user_public_key' && $meta_key !== 'give_user_secret_key' ) {
			return $check;
		}

		$return = $check;

		switch( $meta_key ) {
			case 'give_user_public_key':
				$return = Give()->api->get_user_public_key( $object_id );
				break;
			case 'give_user_secret_key':
				$return = Give()->api->get_user_secret_key( $object_id );
				break;
		}

		if ( ! $single ) {
			$return = array( $return );
		}

		return $return;

	}

}
