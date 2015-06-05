<?php
/**
 * Donor Functions
 *
 * @package     Give
 * @subpackage  Admin/Donors
 * @copyright   Copyright (c) 2015, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register a view for the single donor view
 *
 * @since  1.0
 *
 * @param  array $views An array of existing views
 *
 * @return array        The altered list of views
 */
function give_register_default_donor_views( $views ) {

	$default_views = array(
		'overview' => 'give_donors_view',
		'delete'   => 'give_donors_delete_view',
		'notes'    => 'give_donor_notes_view'
	);

	return array_merge( $views, $default_views );

}

add_filter( 'give_donor_views', 'give_register_default_donor_views', 1, 1 );

/**
 * Register a tab for the single donor view
 *
 * @since  1.0
 *
 * @param  array $tabs An array of existing tabs
 *
 * @return array       The altered list of tabs
 */
function give_register_default_donor_tabs( $tabs ) {

	$default_tabs = array(
		'overview' => array( 'dashicon' => 'dashicons-admin-users', 'title' => __( 'Donor Profile', 'give' ) ),
		'notes'    => array( 'dashicon' => 'dashicons-admin-comments', 'title' => __( 'Donor Notes', 'give' ) )
	);

	return array_merge( $tabs, $default_tabs );
}

add_filter( 'give_donor_tabs', 'give_register_default_donor_tabs', 1, 1 );

/**
 * Register the Delete icon as late as possible so it's at the bottom
 *
 * @since  1.0
 *
 * @param  array $tabs An array of existing tabs
 *
 * @return array       The altered list of tabs, with 'delete' at the bottom
 */
function give_register_delete_donor_tab( $tabs ) {

	$tabs['delete'] = array( 'dashicon' => 'dashicons-trash', 'title' => __( 'Delete Donor', 'give' ) );

	return $tabs;
}

add_filter( 'give_donor_tabs', 'give_register_delete_donor_tab', PHP_INT_MAX, 1 );
