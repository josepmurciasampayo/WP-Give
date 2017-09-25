<?php
/**
 * Admin Notices Class.
 *
 * @package     Give
 * @subpackage  Admin/Notices
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Give_Notices Class
 *
 * @since 1.0
 */
class Give_Notices {
	/**
	 * List of notices
	 * @var array
	 * @since  1.8
	 * @access private
	 */
	private static $notices = array();


	/**
	 * Flag to check if any notice auto dismissible among all notices
	 *
	 * @since  1.8.9
	 * @access private
	 * @var bool
	 */
	private static $has_auto_dismissible_notice = false;

	/**
	 * Flag to check if any notice has dismiss interval among all notices
	 *
	 * @since  1.8.9
	 * @access private
	 * @var bool
	 */
	private static $has_dismiss_interval_notice = false;

	/**
	 * Get things started.
	 *
	 * @since 1.0
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'render_admin_notices' ), 999 );
		add_action( 'give_dismiss_notices', array( $this, 'dismiss_notices' ) );

		add_action( 'give_frontend_notices', array( $this, 'render_frontend_notices' ), 999 );
		add_action( 'give_donation_form_before_personal_info', array( $this, 'render_frontend_notices' ) );
		add_action( 'give_ajax_donation_errors', array( $this, 'render_frontend_notices' ) );
	}

	/**
	 * Register notice.
	 *
	 * @since  1.8.9
	 * @access public
	 *
	 * @param $notice_args
	 *
	 * @return bool
	 */
	public function register_notice( $notice_args ) {
		// Bailout.
		if ( empty( $notice_args['id'] ) || array_key_exists( $notice_args['id'], self::$notices ) ) {
			return false;
		}

		$notice_args = wp_parse_args(
			$notice_args,
			array(
				'id'                    => '',
				'description'           => '',
				/**
				 * Add New Parameter and remove the auto_dismissible parameter.
				 * Value: auto/true/false
				 *
				 * @since 1.8.14
				 */
				'dismissible'           => true,

				// Value: error/warning/success/info/updated
				'type'                  => 'error',

				// Value: null/user/all
				'dismissible_type'      => null,

				// Value: shortly/permanent/null/custom
				'dismiss_interval'      => null,

				// Only set it when custom is defined.
				'dismiss_interval_time' => null,

			)
		);

		/**
		 * Added to give support to backward compatibility to auto_dismissible.
		 * Check if auto_dismissible is set and it true then unset and change dismissible parameter value to auto
		 *
		 * @since 1.8.14
		 */
		if ( isset( $notice_args['auto_dismissible'] ) ) {
			if ( ! empty( $notice_args['auto_dismissible'] ) ) {
				$notice_args['dismissible'] = 'auto';
			}
			// unset auto_dismissible as it has being deprecated.
			unset( $notice_args['auto_dismissible'] );
		}

		// Set extra dismiss links if any.
		if ( false !== strpos( $notice_args['description'], 'data-dismiss-interval' ) ) {

			preg_match_all( "/data-([^\"]*)=\"([^\"]*)\"/", $notice_args['description'], $extra_notice_dismiss_link );

			if ( ! empty( $extra_notice_dismiss_link ) ) {
				$extra_notice_dismiss_links = array_chunk( current( $extra_notice_dismiss_link ), 3 );
				foreach ( $extra_notice_dismiss_links as $extra_notice_dismiss_link ) {
					// Create array og key ==> value by parsing query string created after renaming data attributes.
					$data_attribute_query_str = str_replace( array( 'data-', '-', '"' ), array(
						'',
						'_',
						'',
					), implode( '&', $extra_notice_dismiss_link ) );

					$notice_args['extra_links'][] = wp_parse_args( $data_attribute_query_str );
				}
			}
		}


		self::$notices[ $notice_args['id'] ] = $notice_args;

		// Auto set show param if not already set.
		if ( ! isset( self::$notices[ $notice_args['id'] ]['show'] ) ) {
			self::$notices[ $notice_args['id'] ]['show'] = $this->is_notice_dismissed( $notice_args ) ? false : true;
		}

		// Auto set time interval for shortly.
		if ( 'shortly' === self::$notices[ $notice_args['id'] ]['dismiss_interval'] ) {
			self::$notices[ $notice_args['id'] ]['dismiss_interval_time'] = DAY_IN_SECONDS;
		}

		return true;
	}

	/**
	 * Display notice.
	 *
	 * @since 1.8.9
	 *
	 */
	public function render_admin_notices() {
		// Bailout.
		if ( empty( self::$notices ) ) {
			return;
		}

		$output = '';

		foreach ( self::$notices as $notice_id => $notice ) {
			// Check flag set to true to show notice.
			if ( ! $notice['show'] ) {
				continue;
			}

			// Check if notice dismissible or not.
			if ( ! self::$has_auto_dismissible_notice ) {
				self::$has_auto_dismissible_notice = ( 'auto' === $notice['dismissible'] ? true : false );
			}

			// Check if notice dismissible or not.
			if ( ! self::$has_dismiss_interval_notice ) {
				self::$has_dismiss_interval_notice = $notice['dismiss_interval'];
			}

			$css_id = ( false === strpos( $notice['id'], 'give' ) ? "give-{$notice['id']}" : $notice['id'] );

			$css_class = "give-notice notice ". ( empty( $notice['dismissible'] ) ? 'non': 'is' ) ."-dismissible {$notice['type']} notice-{$notice['type']}";
			$output    .= sprintf(
				'<div id="%1$s" class="%2$s" data-dismissible="%3$s" data-dismissible-type="%4$s" data-dismiss-interval="%5$s" data-notice-id="%6$s" data-security="%7$s" data-dismiss-interval-time="%8$s">' . " \n",
				$css_id,
				$css_class,
				$notice['dismissible'],
				$notice['dismissible_type'],
				$notice['dismiss_interval'],
				$notice['id'],
				empty( $notice['dismissible_type'] ) ? '' : wp_create_nonce( "give_edit_{$notice_id}_notice" ),
				$notice['dismiss_interval_time']
			);

			$output .= ( 0 === strpos( $notice['description'], '<div' ) || 0 === strpos( $notice['description'], '<p' ) ? $notice['description'] : "<p>{$notice['description']}</p>" );
			$output .= "</div> \n";
		}

		echo $output;

		$this->print_js();
	}


	/**
	 * Render give frontend notices.
	 *
	 * @since  1.8.9
	 * @access public
	 *
	 * @param int $form_id
	 */
	public function render_frontend_notices( $form_id = 0 ) {
		$errors = give_get_errors();

		$request_form_id = isset( $_REQUEST['form-id'] ) ? intval( $_REQUEST['form-id'] ) : 0;

		// Sanity checks first: Ensure that gateway returned errors display on the appropriate form.
		if ( ! isset( $_POST['give_ajax'] ) && $request_form_id !== $form_id ) {
			return;
		}

		if ( $errors ) {
			self::print_frontend_errors( $errors );

			give_clear_errors();
		}
	}

	/**
	 * Print notice js.
	 *
	 * @since  1.8.9
	 * @access private
	 */
	private function print_js() {
		if ( self::$has_auto_dismissible_notice ) :
			?>
			<script>
				jQuery(document).ready(function () {
					// auto hide setting message in 5 seconds.
					window.setTimeout(
						function () {
							jQuery('.give-notice[data-dismissible="auto"]').slideUp();
						},
						5000
					);
				})
			</script>
			<?php
		endif;

		if ( self::$has_dismiss_interval_notice ) :
			?>
			<script>
				jQuery(document).ready(function () {
					var $body = jQuery('body');

					$body.on('click', '.give_dismiss_notice', function (e) {
						var $parent            = jQuery(this).parents('.give-notice'),
							custom_notice_data = {
								'dismissible_type'     : jQuery(this).data('dismissible-type'),
								'dismiss_interval'     : jQuery(this).data('dismiss-interval'),
								'dismiss_interval_time': jQuery(this).data('dismiss-interval-time')
							};

						$parent.find('button.notice-dismiss').trigger('click', [custom_notice_data]);
						return false;
					});

					$body.on('click', 'button.notice-dismiss', function (e, custom_notice_data) {
						var $parent            = jQuery(this).parents('.give-notice'),
							custom_notice_data = custom_notice_data || {};

						e.preventDefault();

						var data = {
							'give-action'          : 'dismiss_notices',
							'notice_id'            : $parent.data('notice-id'),
							'dismissible_type'     : $parent.data('dismissible-type'),
							'dismiss_interval'     : $parent.data('dismiss-interval'),
							'dismiss_interval_time': $parent.data('dismiss-interval-time'),
							'_wpnonce'             : $parent.data('security')
						};

						if (Object.keys(custom_notice_data).length) {
							jQuery.extend(data, custom_notice_data);
						}

						// Bailout.
						if (
							!data.dismiss_interval ||
							!data.dismissible_type
						) {
							return false;
						}

						jQuery.post(
							'<?php echo admin_url(); ?>admin-ajax.php',
							data,
							function (response) {

							})
					})
				});
			</script>
			<?php
		endif;
	}


	/**
	 * Hide notice.
	 *
	 * @since  1.8.9
	 * @access public
	 */
	public function dismiss_notices() {
		$_post     = give_clean( $_POST );
		$notice_id = esc_attr( $_post['notice_id'] );

		// Bailout.
		if (
			empty( $notice_id ) ||
			empty( $_post['dismissible_type'] ) ||
			empty( $_post['dismiss_interval'] ) ||
			! check_ajax_referer( "give_edit_{$notice_id}_notice", '_wpnonce' )
		) {
			wp_send_json_error();
		}

		$notice_key = Give()->notices->get_notice_key( $notice_id, $_post['dismiss_interval'] );
		if ( 'user' === $_post['dismissible_type'] ) {
			$current_user = wp_get_current_user();
			$notice_key   = Give()->notices->get_notice_key( $notice_id, $_post['dismiss_interval'], $current_user->ID );
		}

		$notice_dismiss_time = ! empty( $_post['dismiss_interval_time'] ) ? $_post['dismiss_interval_time'] : null;

		// Save option to hide notice.
		Give_Cache::set( $notice_key, true, $notice_dismiss_time, true );

		wp_send_json_success();
	}


	/**
	 * Get notice key.
	 *
	 * @since  1.8.9
	 * @access public
	 *
	 * @param string $notice_id
	 * @param string $dismiss_interval
	 * @param int    $user_id
	 *
	 * @return string
	 */
	public function get_notice_key( $notice_id, $dismiss_interval = null, $user_id = 0 ) {
		$notice_key = "_give_notice_{$notice_id}";

		if ( ! empty( $dismiss_interval ) ) {
			$notice_key .= "_{$dismiss_interval}";
		}

		if ( $user_id ) {
			$notice_key .= "_{$user_id}";
		}

		$notice_key = sanitize_key( $notice_key );

		return $notice_key;
	}


	/**
	 * Get notice dismiss link.
	 *
	 * @param $notice_args
	 *
	 * @return string
	 */
	public function get_dismiss_link( $notice_args ) {
		$notice_args = wp_parse_args(
			$notice_args,
			array(
				'title'                 => __( 'Click here', 'give' ),
				'dismissible_type'      => '',
				'dismiss_interval'      => '',
				'dismiss_interval_time' => null,
			)
		);

		return sprintf(
			'<a href="#" class="give_dismiss_notice" data-dismissible-type="%1$s" data-dismiss-interval="%2$s" data-dismiss-interval-time="%3$s">%4$s</a>',
			$notice_args['dismissible_type'],
			$notice_args['dismiss_interval'],
			$notice_args['dismiss_interval_time'],
			$notice_args['title']
		);
	}


	/**
	 * Check if notice dismissed or not
	 *
	 * @since  1.8.9
	 * @access public
	 *
	 * @param array $notice
	 *
	 * @return bool|null
	 */
	public function is_notice_dismissed( $notice ) {
		$notice_key          = $this->get_notice_key( $notice['id'], $notice['dismiss_interval'] );
		$is_notice_dismissed = false;

		if ( 'user' === $notice['dismissible_type'] ) {
			$current_user = wp_get_current_user();
			$notice_key   = Give()->notices->get_notice_key( $notice['id'], $notice['dismiss_interval'], $current_user->ID );
		}

		$notice_data = Give_Cache::get( $notice_key, true );

		// Find notice dismiss link status if notice has extra dismissible links.
		if ( ( empty( $notice_data ) || is_wp_error( $notice_data ) ) && ! empty( $notice['extra_links'] ) ) {

			foreach ( $notice['extra_links'] as $extra_link ) {
				$new_notice_data = wp_parse_args( $extra_link, $notice );
				unset( $new_notice_data['extra_links'] );

				if ( $is_notice_dismissed = $this->is_notice_dismissed( $new_notice_data ) ) {
					return $is_notice_dismissed;
				}
			}
		}

		$is_notice_dismissed = ! empty( $notice_data ) && ! is_wp_error( $notice_data );

		return $is_notice_dismissed;
	}


	/**
	 * Print frontend errors.
	 *
	 * @since  1.8.9
	 * @access public
	 *
	 * @param $errors
	 */
	static function print_frontend_errors( $errors ) {
		if ( ! $errors ) {
			return;
		}

		$default_notice_args = array(
			'auto_dismissible' => false,
			'dismiss_interval' => 5000,
		);

		// Note: we will remove give_errors class in future.
		$classes = apply_filters( 'give_error_class', array( 'give_notices', 'give_errors' ) );

		echo sprintf( '<div class="%s">', implode( ' ', $classes ) );

			// Loop error codes and display errors.
			foreach ( $errors as $error_id => $error ) {
				// Backward compatibility v<1.8.11
				if ( is_string( $error ) ) {
					$error = array(
						'message'     => $error,
						'notice_args' => array(),
					);
				}

				$notice_args = wp_parse_args( $error['notice_args'], $default_notice_args );

				echo sprintf(
					'<div class="give_error give_notice" id="give_error_%1$s" data-auto-dismissible="%2$d" data-dismiss-interval="%3$d">
								<p><strong>%4$s</strong>: %5$s</p>
							</div>',
					$error_id,
					absint( $notice_args['auto_dismissible'] ),
					absint( $notice_args['dismiss_interval'] ),
					esc_html__( 'Error', 'give' ),
					$error['message']
				);
			}

		echo '</div>';
	}

	/**
	 * Print frontend notice.
	 * Notice: notice type can be success/error/warning
	 *
	 * @since  1.8.9
	 * @access public
	 *
	 * @param        $message
	 * @param bool   $echo
	 * @param string $notice_type
	 * @param array  $notice_args
	 *
	 * @return  string
	 */
	static function print_frontend_notice( $message, $echo = true, $notice_type = 'warning', $notice_args = array() ) {
		if ( empty( $message ) ) {
			return '';
		}

		/**
		 * Change auto_dismissible to dismissible and set the value to true
		 *
		 * @since 1.8.14
		 */
		$default_notice_args = array(
			'dismissible' => true,
			'dismiss_interval' => 5000,
		);

		$notice_args = wp_parse_args( $notice_args, $default_notice_args );

		/**
		 * Added to give support to backward compatibility to auto_dismissible.
		 * Check if auto_dismissible is set and it true then unset and change dismissible parameter value to auto
		 *
		 * @since 1.8.14
		 */
		if ( isset( $notice_args['auto_dismissible'] ) ) {
			if ( ! empty( $notice_args['auto_dismissible'] ) ) {
				$notice_args['dismissible'] = 'auto';
			}
			// unset auto_dismissible as it has being deprecated.
			unset( $notice_args['auto_dismissible'] );
		}

		// Note: we will remove give_errors class in future.
		$error = sprintf(
			'<div class="give_notices give_errors" id="give_error_%1$s">
				<p class="give_error give_notice give_%1$s" data-dismissible="%2$s" data-dismiss-interval="%3$d">
					%4$s
				</p>
			</div>',
			$notice_type,
			give_clean( $notice_args['auto_dismissible'] ),
			absint( $notice_args['dismiss_interval'] ),
			$message
		);

		if ( ! $echo ) {
			return $error;
		}

		echo $error;
	}
}