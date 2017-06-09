<?php
/**
 * Error Tracking
 *
 * @package     Give
 * @subpackage  Functions/Errors
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get Errors
 *
 * Retrieves all error messages stored during the checkout process.
 * If errors exist, they are returned.
 *
 * @since 1.0
 * @uses  Give_Session::get()
 * @return mixed array if errors are present, false if none found
 */
function give_get_errors() {
	return Give()->session->get( 'give_errors' );
}

/**
 * Set Error
 *
 * Stores an error in a session var.
 *
 * @since 1.0
 * @uses  Give_Session::get()
 *
 * @param int    $error_id      ID of the error being set.
 * @param string $error_message Message to store with the error.
 *
 * @return void
 */
function give_set_error( $error_id, $error_message ) {
	$errors = give_get_errors();
	if ( ! $errors ) {
		$errors = array();
	}
	$errors[ $error_id ] = $error_message;
	Give()->session->set( 'give_errors', $errors );
}

/**
 * Clears all stored errors.
 *
 * @since 1.0
 * @uses  Give_Session::set()
 * @return void
 */
function give_clear_errors() {
	Give()->session->set( 'give_errors', null );
}

/**
 * Removes (unsets) a stored error
 *
 * @since 1.0
 * @uses  Give_Session::set()
 *
 * @param int $error_id ID of the error being set.
 *
 * @return void
 */
function give_unset_error( $error_id ) {
	$errors = give_get_errors();
	if ( $errors ) {
		unset( $errors[ $error_id ] );
		Give()->session->set( 'give_errors', $errors );
	}
}

/**
 * Register die handler for give_die()
 *
 * @since  1.0
 * @return string
 */
function _give_die_handler() {
	if ( defined( 'GIVE_UNIT_TESTS' ) ) {
		return '_give_die_handler';
	} else {
		die();
	}
}

/**
 * Wrapper function for wp_die(). This function adds filters for wp_die() which
 * kills execution of the script using wp_die(). This allows us to then to work
 * with functions using give_die() in the unit tests.
 *
 * @since 1.0
 *
 * @param string $message Message to store with the error.
 * @param string $title   Error title.
 * @param int    $status  HTTP status code..
 *
 * @return void
 */
function give_die( $message = '', $title = '', $status = 400 ) {
	add_filter( 'wp_die_ajax_handler', '_give_die_handler', 10, 3 );
	add_filter( 'wp_die_handler', '_give_die_handler', 10, 3 );
	wp_die( $message, $title, array( 'response' => $status ) );
}

/**
 * Give Output Error
 *
 * Helper function to easily output an error message properly wrapped; used commonly with shortcodes
 *
 * @since      1.3
 *
 * @param string $message  Message to store with the error.
 * @param bool   $echo     Flag to print or return output.
 * @param string $error_id ID of the error being set.
 *
 * @return   string  $error
 */
function give_output_error( $message, $echo = true, $error_id = 'warning' ) {
	$error = '<div class="give_errors" id="give_error_' . $error_id . '"><p class="give_error  give_' . $error_id . '">' . $message . '</p></div>';

	if ( $echo ) {
		echo $error;
	} else {
		return $error;
	}

}
