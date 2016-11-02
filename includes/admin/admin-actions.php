<?php
/**
 * Admin Actions
 *
 * @package     Give
 * @subpackage  Admin/Actions
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Hide subscription notice if admin click on "Click here if already renewed" in subscription notice.
 *
 * @since 1.7
 * @return void
 */
function give_hide_subscription_notices() {

	// Hide subscription notices permanently.
	if ( ! empty( $_GET['_give_hide_license_notices_permanently'] ) ) {
		$current_user = wp_get_current_user();

		// check previously disabled notice ids.
		$already_dismiss_notices = ( $already_dismiss_notices = get_user_meta( $current_user->ID, '_give_hide_license_notices_permanently', true ) )
			? $already_dismiss_notices
			: array();

		// Get notice id.
		$notice_id = sanitize_text_field( $_GET['_give_hide_license_notices_permanently'] );

		if ( ! in_array( $notice_id, $already_dismiss_notices ) ) {
			$already_dismiss_notices[] = $notice_id;
		}

		// Store subscription ids.
		update_user_meta( $current_user->ID, '_give_hide_license_notices_permanently', $already_dismiss_notices );

		// Redirect user.
		wp_safe_redirect( remove_query_arg( '_give_hide_license_notices_permanently', $_SERVER['REQUEST_URI'] ) );
		exit();
	}

	// Hide subscription notices shortly.
	if ( ! empty( $_GET['_give_hide_license_notices_shortly'] ) ) {
		$current_user = wp_get_current_user();

		// Get notice id.
		$notice_id = sanitize_text_field( $_GET['_give_hide_license_notices_shortly'] );

		// Transient key name.
		$transient_key = "_give_hide_license_notices_shortly_{$current_user->ID}_{$notice_id}";

		if ( get_transient( $transient_key ) ) {
			return;
		}

		// Hide notice for 24 hours.
		set_transient( $transient_key, true, 24 * HOUR_IN_SECONDS );

		// Redirect user.
		wp_safe_redirect( remove_query_arg( '_give_hide_license_notices_shortly', $_SERVER['REQUEST_URI'] ) );
		exit();
	}
}

add_action( 'admin_init', 'give_hide_subscription_notices' );


/**
 * Verify settings.
 *
 * 1. success and failure page should not be same.
 *
 * @since 1.8
 */
function give_verify_settings() {
	// Bailout.
	if ( ! isset( $_POST['_give-save-settings'] ) ) {
		return;
	}

	$is_redirect = false;

	switch ( give_get_current_setting_tab() ) {
		case 'general' :
			// Check if success page and failure page are same or not.
			if (
				isset( $_POST['success_page'] )
				&& isset( $_POST['failure_page'] )
				&& ( $_POST['success_page'] === $_POST['failure_page'] )
			) {
				$is_redirect = true;
			}
			break;
	}

	// Bailout.
	if ( ! $is_redirect ) {
		return;
	}


	// Redirect.
	wp_safe_redirect(
		add_query_arg(
			'give-message',
			'matched-success-failure-page',
			esc_url_raw( $_SERVER['REQUEST_URI'] )
		)
	);
	exit();
}

add_action( 'admin_init', 'give_verify_settings' );