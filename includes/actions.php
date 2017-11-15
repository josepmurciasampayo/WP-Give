<?php
/**
 * Front-end Actions
 *
 * @package     Give
 * @subpackage  Functions
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hooks Give actions, when present in the $_GET superglobal. Every give_action
 * present in $_GET is called using WordPress's do_action function. These
 * functions are called on init.
 *
 * @since  1.0
 *
 * @return void
 */
function give_get_actions() {

	$_get_action = ! empty( $_GET['give_action'] ) ? $_GET['give_action'] : null;

	// Add backward compatibility to give-action param ( $_GET )
	if ( empty( $_get_action ) ) {
		$_get_action = ! empty( $_GET['give-action'] ) ? $_GET['give-action'] : null;
	}

	if ( isset( $_get_action ) ) {
		/**
		 * Fires in WordPress init or admin init, when give_action is present in $_GET.
		 *
		 * @since 1.0
		 *
		 * @param array $_GET Array of HTTP GET variables.
		 */
		do_action( "give_{$_get_action}", $_GET );
	}

}

add_action( 'init', 'give_get_actions' );

/**
 * Hooks Give actions, when present in the $_POST super global. Every give_action
 * present in $_POST is called using WordPress's do_action function. These
 * functions are called on init.
 *
 * @since  1.0
 *
 * @return void
 */
function give_post_actions() {

	$_post_action = ! empty( $_POST['give_action'] ) ? $_POST['give_action'] : null;


	// Add backward compatibility to give-action param ( $_POST ).
	if ( empty( $_post_action ) ) {
		$_post_action = ! empty( $_POST['give-action'] ) ? $_POST['give-action'] : null;
	}

	if ( isset( $_post_action ) ) {
		/**
		 * Fires in WordPress init or admin init, when give_action is present in $_POST.
		 *
		 * @since 1.0
		 *
		 * @param array $_POST Array of HTTP POST variables.
		 */
		do_action( "give_{$_post_action}", $_POST );
	}

}

add_action( 'init', 'give_post_actions' );

/**
 * Connect WordPress user with Donor.
 *
 * @since  1.7
 *
 * @param  int   $user_id   User ID
 * @param  array $user_data User Data
 *
 * @return void
 */
function give_connect_donor_to_wpuser( $user_id, $user_data ) {
	/* @var Give_Donor $donor */
	$donor = new Give_Donor( $user_data['user_email'] );

	// Validate donor id and check if do nor is already connect to wp user or not.
	if ( $donor->id && ! $donor->user_id ) {

		// Update donor user_id.
		if ( $donor->update( array( 'user_id' => $user_id ) ) ) {
			$donor_note = sprintf( esc_html__( 'WordPress user #%d is connected to #%d', 'give' ), $user_id, $donor->id );
			$donor->add_note( $donor_note );

			// Update user_id meta in payments.
			// if( ! empty( $donor->payment_ids ) && ( $donations = explode( ',', $donor->payment_ids ) ) ) {
			// 	foreach ( $donations as $donation  ) {
			// 		give_update_meta( $donation, '_give_payment_user_id', $user_id );
			// 	}
			// }
			// Do not need to update user_id in payment because we will get user id from donor id now.
		}
	}
}

add_action( 'give_insert_user', 'give_connect_donor_to_wpuser', 10, 2 );


/**
 * Setup site home url check
 *
 * Note: if location of site changes then run cron to validate licenses
 *
 * @since   1.7
 * @updated 1.8.15 - Resolved issue with endless looping because of URL mismatches.
 * @return void
 */
function give_validate_license_when_site_migrated() {
	// Store current site address if not already stored.
	$home_url_parts              = parse_url( home_url() );
	$home_url                    = isset( $home_url_parts['host'] ) ? $home_url_parts['host'] : false;
	$home_url                    .= isset( $home_url_parts['path'] ) ? $home_url_parts['path'] : '';
	$site_address_before_migrate = get_option( 'give_site_address_before_migrate' );

	// Need $home_url to proceed
	if ( ! $home_url ) {
		return;
	}

	// Save site address
	if ( ! $site_address_before_migrate ) {
		// Update site address.
		update_option( 'give_site_address_before_migrate', $home_url );

		return;
	}

	// Backwards compat. for before when we were storing URL scheme.
	if ( strpos( $site_address_before_migrate, 'http' ) ) {
		$site_address_before_migrate = parse_url( $site_address_before_migrate );
		$site_address_before_migrate = isset( $site_address_before_migrate['host'] ) ? $site_address_before_migrate['host'] : false;
		// Add path for multisite installs.
		$site_address_before_migrate .= isset( $site_address_before_migrate['path'] ) ? $site_address_before_migrate['path'] : '';
	}

	// If the two URLs don't match run CRON.
	if ( $home_url !== $site_address_before_migrate ) {
		// Immediately run cron.
		wp_schedule_single_event( time(), 'give_validate_license_when_site_migrated' );

		// Update site address.
		update_option( 'give_site_address_before_migrate', $home_url );
	}

}

add_action( 'admin_init', 'give_validate_license_when_site_migrated' );


/**
 * Processing after donor batch export complete
 *
 * @since 1.8
 *
 * @param $data
 */
function give_donor_batch_export_complete( $data ) {
	// Remove donor ids cache.
	if (
		isset( $data['class'] )
		&& 'Give_Batch_Donors_Export' === $data['class']
		&& ! empty( $data['forms'] )
		&& isset( $data['give_export_option']['query_id'] )
	) {
		Give_Cache::delete( Give_Cache::get_key( $data['give_export_option']['query_id'] ) );
	}
}

add_action( 'give_file_export_complete', 'give_donor_batch_export_complete' );

/**
 * Print css for wordpress setting pages.
 *
 * @since 1.8.7
 */
function give_admin_quick_css() {
	/* @var WP_Screen $screen */
	$screen = get_current_screen();

	if ( ! ( $screen instanceof WP_Screen ) ) {
		return false;
	}

	switch ( true ) {
		case ( 'plugins' === $screen->base || 'plugins-network' === $screen->base ):
			?>
			<style>
				tr.active.update + tr.give-addon-notice-tr td {
					box-shadow: none;
					-webkit-box-shadow: none;
				}

				tr.active + tr.give-addon-notice-tr td {
					position: relative;
					top: -1px;
				}

				tr.active + tr.give-addon-notice-tr .notice {
					margin: 5px 20px 15px 40px;
				}

				tr.give-addon-notice-tr .dashicons {
					color: #f56e28;
				}

				tr.give-addon-notice-tr td {
					border-left: 4px solid #00a0d2;
				}

				tr.give-addon-notice-tr td {
					padding: 0 !important;
				}

				tr.active.update + tr.give-addon-notice-tr .notice {
					margin: 5px 20px 5px 40px;
				}
			</style>
			<?php
	}
}

add_action( 'admin_head', 'give_admin_quick_css' );


/**
 * Set Donation Amount for Multi Level Donation Forms
 *
 * @param int $form_id
 *
 * @since 1.8.9
 *
 * @return void
 */
function give_set_donation_levels_max_min_amount( $form_id ) {
	if (
		( 'set' === $_POST['_give_price_option'] ) ||
		( in_array( '_give_donation_levels', $_POST ) && count( $_POST['_give_donation_levels'] ) <= 0 ) ||
		! ( $donation_levels_amounts = wp_list_pluck( $_POST['_give_donation_levels'], '_give_amount' ) )
	) {
		// Delete old meta.
		give_delete_meta( $form_id, '_give_levels_minimum_amount' );
		give_delete_meta( $form_id, '_give_levels_maximum_amount' );

		return;
	}

	// Sanitize donation level amounts.
	$donation_levels_amounts = array_map( 'give_maybe_sanitize_amount', $donation_levels_amounts );

	$min_amount = min( $donation_levels_amounts );
	$max_amount = max( $donation_levels_amounts );

	// Set Minimum and Maximum amount for Multi Level Donation Forms
	give_update_meta( $form_id, '_give_levels_minimum_amount', $min_amount ? give_sanitize_amount_for_db( $min_amount ) : 0 );
	give_update_meta( $form_id, '_give_levels_maximum_amount', $max_amount ? give_sanitize_amount_for_db( $max_amount ) : 0 );
}

add_action( 'give_pre_process_give_forms_meta', 'give_set_donation_levels_max_min_amount', 30 );


/**
 * Save donor address when donation complete
 *
 * @since 2.0
 *
 * @param int $payment_id
 */
function _give_save_donor_billing_address( $payment_id ) {
	/* @var Give_Payment $donation */
	$donation = new Give_Payment( $payment_id );

	// Bailout
	if ( ! $donation->customer_id ) {
		return;
	}


	/* @var Give_Donor $donor */
	$donor = new Give_Donor( $donation->customer_id );

	// Save address.
	$donor->add_address( 'billing[]', $donation->address );
}

add_action( 'give_complete_donation', '_give_save_donor_billing_address', 9999 );


/**
 * Update form id in payment logs
 * This function will be use by cron to sync form id ( if changes ) between payment and log.
 *
 * @since 2.0
 *
 * @param $new_form_id
 * @param $payment_id
 */
function __give_update_log_form_id( $new_form_id, $payment_id ) {
	$logs = Give()->logs->get_logs( $payment_id );

	// Bailout.
	if ( empty( $logs ) ) {
		return;
	}

	/* @var object $log */
	foreach ( $logs as $log ) {
		Give()->logs->logmeta_db->update_meta( $log->ID, '_give_log_form_id', $new_form_id );
	}

	// Delete cache.
	Give()->logs->delete_cache();
}

/**
 * Update Donor Information when User Profile is updated from admin.
 * Note: for internal use only.
 *
 * @param int $user_id
 *
 * @access public
 * @since  2.0
 *
 * @return bool
 */
function give_update_donor_name_on_user_update( $user_id = 0 ) {

	if ( current_user_can( 'edit_user', $user_id ) ) {

		$donor = new Give_Donor( $user_id, true );

		// Bailout, if donor doesn't exists.
		if ( ! $donor ) {
			return false;
		}

		// Get User First name and Last name.
		$first_name = ( $_POST['first_name'] ) ? give_clean( $_POST['first_name'] ) : get_user_meta( $user_id, 'first_name', true );
		$last_name  = ( $_POST['last_name'] ) ? give_clean( $_POST['last_name'] ) : get_user_meta( $user_id, 'last_name', true );
		$full_name  = strip_tags( wp_unslash( trim( "{$first_name} {$last_name}" ) ) );

		// Assign User First name and Last name to Donor.
		Give()->donors->update( $donor->id, array( 'name' => $full_name ) );
		Give()->donor_meta->update_meta( $donor->id, '_give_donor_first_name', $first_name );
		Give()->donor_meta->update_meta( $donor->id, '_give_donor_last_name', $last_name );

	}
}

add_action( 'edit_user_profile_update', 'give_update_donor_name_on_user_update', 10 );
add_action( 'personal_options_update', 'give_update_donor_name_on_user_update', 10 );


/**
 * Updates the email address of a donor record when the email on a user is updated
 * Note: for internal use only.
 *
 * @since  1.4.3
 * @access public
 *
 * @param  int          $user_id       User ID.
 * @param  WP_User|bool $old_user_data User data.
 *
 * @return bool
 */
function give_update_donor_email_on_user_update( $user_id = 0, $old_user_data = false ) {

	$donor = new Give_Donor( $user_id, true );

	if ( ! $donor ) {
		return false;
	}

	$user = get_userdata( $user_id );

	if ( ! empty( $user ) && $user->user_email !== $donor->email ) {

		if ( ! $this->get_donor_by( 'email', $user->user_email ) ) {

			$success = $this->update( $donor->id, array( 'email' => $user->user_email ) );

			if ( $success ) {
				// Update some payment meta if we need to
				$payments_array = explode( ',', $donor->payment_ids );

				if ( ! empty( $payments_array ) ) {

					foreach ( $payments_array as $payment_id ) {

						give_update_payment_meta( $payment_id, 'email', $user->user_email );

					}

				}

				/**
				 * Fires after updating donor email on user update.
				 *
				 * @since 1.4.3
				 *
				 * @param  WP_User    $user  WordPress User object.
				 * @param  Give_Donor $donor Give donor object.
				 */
				do_action( 'give_update_donor_email_on_user_update', $user, $donor );

			}

		}

	}

}

add_action( 'profile_update', array( $this, 'give_update_donor_email_on_user_update' ), 10, 2 );


