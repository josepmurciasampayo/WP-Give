<?php
/**
 * Donate Form Object
 *
 * @package     Give
 * @subpackage  Classes/Forms
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

/**
 * Give_Donate_Form Class
 *
 * @since 1.0
 */
class Give_Donate_Form {

	/**
	 * The donation ID
	 *
	 * @since 1.0
	 */
	public $ID = 0;

	/**
	 * The donation price
	 *
	 * @since 1.0
	 */
	private $price;

	/**
	 * The minimum donation price
	 *
	 * @since 1.3.6
	 */
	private $minimum_price;

	/**
	 * The donation goal
	 *
	 * @since 1.0
	 */
	private $goal;

	/**
	 * The donation prices, if Price Levels are enabled
	 *
	 * @since 1.0
	 */
	private $prices;

	/**
	 * The form's sale count
	 *
	 * @since 1.0
	 */
	private $sales;

	/**
	 * The form's total earnings
	 *
	 * @since 1.0
	 */
	private $earnings;

	/**
	 * Give_Donate_Form constructor.
	 *
	 * @since 1.0
	 *
	 * @param bool $_id
	 * @param array $_args
	 */
	public function __construct( $_id = false, $_args = array() ) {

		if ( false === $_id ) {

			$defaults = array(
				'post_type'   => 'give_forms',
				'post_status' => 'draft',
				'post_title'  => __( 'New Give Form', 'give' )
			);

			$args = wp_parse_args( $_args, $defaults );

			$_id = wp_insert_post( $args, true );

		}

		$donate_form = WP_Post::get_instance( $_id );

		if ( ! is_object( $donate_form ) ) {
			return false;
		}

		if ( ! is_a( $donate_form, 'WP_Post' ) ) {
			return false;
		}

		if ( 'give_forms' !== $donate_form->post_type ) {
			return false;
		}

		foreach ( $donate_form as $key => $value ) {

			$this->$key = $value;

		}

	}

	/**
	 * Magic __get function to dispatch a call to retrieve a private property
	 *
	 * @since 1.0
	 *
	 * @param $key
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function __get( $key ) {

		if ( method_exists( $this, 'get_' . $key ) ) {

			return call_user_func( array( $this, 'get_' . $key ) );

		} else {

			throw new Exception( 'Can\'t get property ' . $key );

		}

	}

	/**
	 * Retrieve the ID
	 *
	 * @since 1.0
	 * @return int
	 */
	public function get_ID() {

		return $this->ID;

	}

	/**
	 * Retrieve the price
	 *
	 * @since 1.0
	 * @return float
	 */
	public function get_price() {

		if ( ! isset( $this->price ) ) {

			$this->price = get_post_meta( $this->ID, '_give_set_price', true );

			if ( $this->price ) {

				$this->price = give_sanitize_amount( $this->price );

			} else {

				$this->price = 0;

			}

		}

		return apply_filters( 'give_get_set_price', $this->price, $this->ID );
	}

	/**
	 * Retrieve the minimum price
	 *
	 * @since 1.3.6
	 * @return float
	 */
	public function get_minimum_price() {

		if ( ! isset( $this->minimum_price ) ) {

			$allow_custom_amount = get_post_meta( $this->ID, '_give_custom_amount', true );
			$this->minimum_price = get_post_meta( $this->ID, '_give_custom_amount_minimum', true );

			if ( $allow_custom_amount != 'no' && $this->minimum_price ) {

				$this->minimum_price = give_sanitize_amount( $this->minimum_price );

			} else {

				$this->minimum_price = 0;

			}

		}

		return apply_filters( 'give_get_set_minimum_price', $this->minimum_price, $this->ID );
	}

	/**
	 * Retrieve the variable prices
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_prices() {

		if ( ! isset( $this->prices ) ) {

			$this->prices = get_post_meta( $this->ID, '_give_donation_levels', true );

		}

		return apply_filters( 'give_get_donation_levels', $this->prices, $this->ID );

	}

	/**
	 * Retrieve the goal
	 *
	 * @since 1.0
	 * @return float
	 */
	public function get_goal() {

		if ( ! isset( $this->goal ) ) {

			$this->goal = get_post_meta( $this->ID, '_give_set_goal', true );

			if ( $this->goal ) {

				$this->goal = give_sanitize_amount( $this->goal );

			} else {

				$this->goal = 0;

			}

		}

		return apply_filters( 'give_get_set_goal', $this->goal, $this->ID );

	}

	/**
	 * Determine if single price mode is enabled or disabled
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function is_single_price_mode() {

		$option = get_post_meta( $this->ID, '_give_price_option', true );
		$ret    = 0;

		if ( empty( $option ) || $option === 'set' ) {
			$ret = 1;
		}

		return (bool) apply_filters( 'give_single_price_option_mode', $ret, $this->ID );

	}

	/**
	 * Has Variable Prices
	 *
	 * @description Determine if the donation form has variable prices enabled
	 *
	 * @since       1.0
	 * @return bool
	 */
	public function has_variable_prices() {

		$option = get_post_meta( $this->ID, '_give_price_option', true );
		$ret    = 0;

		if ( $option === 'multi' ) {
			$ret = 1;
		}

		return (bool) apply_filters( 'give_has_variable_prices', $ret, $this->ID );

	}


	/**
	 * Retrieve the sale count for the download
	 *
	 * @since 1.0
	 * @return int
	 */
	public function get_sales() {

		if ( ! isset( $this->sales ) ) {

			if ( '' == get_post_meta( $this->ID, '_give_form_sales', true ) ) {
				add_post_meta( $this->ID, '_give_form_sales', 0 );
			} // End if

			$this->sales = get_post_meta( $this->ID, '_give_form_sales', true );

			if ( $this->sales < 0 ) {
				// Never let sales be less than zero
				$this->sales = 0;
			}

		}

		return $this->sales;

	}

	/**
	 * Increment the sale count by one
	 *
	 * @since 1.0
	 *
	 * @param int $quantity
	 *
	 * @return bool|int
	 */
	public function increase_sales( $quantity = 1 ) {

		$sales       = give_get_form_sales_stats( $this->ID );
		$quantity    = absint( $quantity );
		$total_sales = $sales + $quantity;

		if ( update_post_meta( $this->ID, '_give_form_sales', $total_sales ) ) {
			$this->sales = $sales;

			return $sales;
		}

		return false;
	}

	/**
	 * Decrement the sale count by one
	 *
	 * @since 1.0
	 * @return int|false
	 */
	public function decrease_sales( $quantity = 1 ) {

		$sales = give_get_form_sales_stats( $this->ID );
		// Only decrease if not already zero
		if ( $sales > 0 ) {
			$quantity    = absint( $quantity );
			$total_sales = $sales - $quantity;

			if ( $this->update_meta( '_give_form_sales', $total_sales ) ) {

				$this->sales = $total_sales;

				return $this->sales;

			}

		}

		return false;

	}

	/**
	 * Retrieve the total earnings for the form
	 *
	 * @since 1.0
	 * @return float
	 */
	public function get_earnings() {

		if ( ! isset( $this->earnings ) ) {

			if ( '' == get_post_meta( $this->ID, '_give_form_earnings', true ) ) {
				add_post_meta( $this->ID, '_give_form_earnings', 0 );
			}

			$this->earnings = get_post_meta( $this->ID, '_give_form_earnings', true );

			if ( $this->earnings < 0 ) {
				// Never let earnings be less than zero
				$this->earnings = 0;
			}

		}

		return $this->earnings;

	}

	/**
	 * Increase the earnings by the given amount
	 *
	 * @since 1.0
	 * @return float|false
	 */
	public function increase_earnings( $amount = 0 ) {

		$earnings = give_get_form_earnings_stats( $this->ID );
		$earnings = $earnings + (float) $amount;

		if ( update_post_meta( $this->ID, '_give_form_earnings', $earnings ) ) {
			$this->earnings = $earnings;

			return $earnings;
		}

		return false;

	}

	/**
	 * Decrease the earnings by the given amount
	 *
	 * @since 1.0
	 * @return float|false
	 */
	public function decrease_earnings( $amount ) {

		$earnings = give_get_form_earnings_stats( $this->ID );

		if ( $earnings > 0 ) // Only decrease if greater than zero
		{
			$earnings = $earnings - (float) $amount;
		}

		if ( update_post_meta( $this->ID, '_give_form_earnings', $earnings ) ) {
			$this->earnings = $earnings;

			return $earnings;
		}

		return false;

	}

	/**
	 * Determine if the donation is free or if the given price ID is free
	 *
	 * @since 1.0
	 * @return bool
	 */
	public function is_free( $price_id = false ) {

		$is_free          = false;
		$variable_pricing = give_has_variable_prices( $this->ID );

		if ( $variable_pricing && ! is_null( $price_id ) && $price_id !== false ) {
			$price = give_get_price_option_amount( $this->ID, $price_id );
		} elseif ( ! $variable_pricing ) {
			$price = get_post_meta( $this->ID, '_give_set_price', true );
		}

		if ( isset( $price ) && (float) $price == 0 ) {
			$is_free = true;
		}

		return (bool) apply_filters( 'give_is_free_donation', $is_free, $this->ID, $price_id );

	}

	/**
	 * Updates a single meta entry for the donation form
	 *
	 * @since  1.5
	 * @access private
	 *
	 * @param  string $meta_key The meta_key to update
	 * @param  string|array|object $meta_value The value to put into the meta
	 *
	 * @return bool             The result of the update query
	 */
	private function update_meta( $meta_key = '', $meta_value = '' ) {

		global $wpdb;

		if ( empty( $meta_key ) || empty( $meta_value ) ) {
			return false;
		}

		// Make sure if it needs to be serialized, we do
		$meta_value = maybe_serialize( $meta_value );

		if ( is_numeric( $meta_value ) ) {
			$value_type = is_float( $meta_value ) ? '%f' : '%d';
		} else {
			$value_type = "'%s'";
		}

		$sql = $wpdb->prepare( "UPDATE $wpdb->postmeta SET meta_value = $value_type WHERE post_id = $this->ID AND meta_key = '%s'", $meta_value, $meta_key );

		if ( $wpdb->query( $sql ) ) {

			clean_post_cache( $this->ID );

			return true;

		}

		return false;
	}

}
