<?php
/**
 * Delete Donors.
 *
 * This class handles batch processing of deleting donor data.
 *
 * @subpackage  Admin/Tools/Give_Tools_Delete_Donors
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.8.8
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Give_Tools_Delete_Test_Transactions Class
 *
 * @since 1.8.8
 */
class Give_Tools_Delete_Donors extends Give_Batch_Export {

	/**
	 * Our export type. Used for export-type specific filters/actions
	 * @var string
	 * @since 1.8.8
	 */
	public $export_type = '';

	/**
	 * Allows for a non-form batch processing to be run.
	 * @since  1.8.8
	 * @var boolean
	 */
	public $is_void = true;

	/**
	 * Sets the number of items to pull on each step.
	 * @since  1.8.8
	 * @var integer
	 */
	public $per_step = 30;

	/**
	 * Get the Export Data
	 *
	 * @access public
	 * @since 1.8.8
	 * @global object $wpdb Used to query the database using the WordPress Database API
	 *
	 * @return array|bool $data The data for the CSV file
	 */
	public function get_data() {
		global $wpdb;


		if ( $step_items = 1) {

			$query = '';
			$wpdb->query( $query );

			return true;

		}

		return false;

	}

	/**
	 * Return the calculated completion percentage
	 *
	 * @since 1.8.8
	 * @return int
	 */
	public function get_percentage_complete() {

		$items = $this->get_stored_data( 'give_temp_delete_donors', false );
		$total = count( $items );

		$percentage = 100;

		if ( $total > 0 ) {
			$percentage = ( ( $this->per_step * $this->step ) / $total ) * 100;
		}

		if ( $percentage > 100 ) {
			$percentage = 100;
		}

		return $percentage;
	}

	/**
	 * Set the properties specific to the payments export
	 *
	 * @since 1.8.8
	 *
	 * @param array $request The Form Data passed into the batch processing
	 */
	public function set_properties( $request ) {
	}

	/**
	 * Process a step
	 *
	 * @since 1.8.8
	 * @return bool
	 */
	public function process_step() {

		if ( ! $this->can_export() ) {
			wp_die( __( 'You do not have permission to delete test transactions.', 'give' ), __( 'Error', 'give' ), array( 'response' => 403 ) );
		}

		$had_data = $this->get_data();

		if ( $had_data ) {
			$this->done = false;

			return true;
		} else {

			$this->delete_data( 'give_temp_delete_donors' );


			$this->done    = true;
			$this->message = __( 'Donor\'s successfully deleted.', 'give' );

			return false;
		}
	}

//	/**
//	 * Headers
//	 */
//	public function headers() {
//		ignore_user_abort( true );
//
//		if ( ! give_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
//			set_time_limit( 0 );
//		}
//	}
//
//	/**
//	 * Perform the export
//	 *
//	 * @access public
//	 * @since 1.8.8
//	 * @return void
//	 */
//	public function export() {
//
//		// Set headers
//		$this->headers();
//
//		give_die();
//	}

	/**
	 * Pre Fetch
	 */
	public function pre_fetch() {

		if ( $this->step == 1 ) {
			$this->delete_data( 'give_temp_delete_donors' );
		}

		$items = get_option( 'give_temp_delete_donors', false );

		if ( false === $items ) {
			$items = array();

			$args = apply_filters( 'give_tools_reset_stats_total_args', array(
				'post_type'      => 'give_payment',
				'post_status'    => 'any',
				'posts_per_page' => - 1,
				// ONLY TEST MODE TRANSACTIONS!!!
				'meta_key'   => '_give_payment_mode',
				'meta_value' => 'test'
			) );

			$posts = get_posts( $args );
			foreach ( $posts as $post ) {
				$items[] = array(
					'id'   => (int) $post->ID,
					'type' => $post->post_type,
				);
			}

			// Allow filtering of items to remove with an unassociative array for each item.
			// The array contains the unique ID of the item, and a 'type' for you to use in the execution of the get_data method.
			$items = apply_filters( 'give_delete_test_items', $items );

			$this->store_data( 'give_temp_delete_donors', $items );
		}

	}

	/**
	 * Given a key, get the information from the Database Directly
	 *
	 * @since  1.8.8
	 *
	 * @param  string $key The option_name
	 *
	 * @return mixed       Returns the data from the database
	 */
	private function get_stored_data( $key ) {
		global $wpdb;
		$value = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = '%s'", $key ) );

		return empty( $value ) ? false : maybe_unserialize( $value );
	}

	/**
	 * Give a key, store the value
	 *
	 * @since  1.8.8
	 *
	 * @param  string $key The option_name
	 * @param  mixed $value The value to store
	 *
	 * @return void
	 */
	private function store_data( $key, $value ) {
		global $wpdb;

		$value = maybe_serialize( $value );

		$data = array(
			'option_name'  => $key,
			'option_value' => $value,
			'autoload'     => 'no',
		);

		$formats = array(
			'%s',
			'%s',
			'%s',
		);

		$wpdb->replace( $wpdb->options, $data, $formats );
	}

	/**
	 * Delete an option
	 *
	 * @since  1.8.8
	 *
	 * @param  string $key The option_name to delete
	 *
	 * @return void
	 */
	private function delete_data( $key ) {
		global $wpdb;
		$wpdb->delete( $wpdb->options, array( 'option_name' => $key ) );
	}

}
