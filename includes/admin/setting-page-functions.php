<?php
/**
 * Get current setting tab.
 *
 * @since  1.8
 * @return string
 */
function give_get_current_setting_tab() {
	// Get current setting page.
	$current_setting_page = give_get_current_setting_page();

	/**
	 * Filter the default tab for current setting page.
	 *
	 * @since 1.8
	 *
	 * @param string
	 */
	$default_current_tab = apply_filters( "give_default_setting_tab_{$current_setting_page}", 'general' );

	// Get current tab.
	$current_tab = empty( $_GET['tab'] ) ? $default_current_tab : urldecode( $_GET['tab'] );

	// Output.
	return $current_tab;
}


/**
 * Get current setting section.
 *
 * @since  1.8
 * @return string
 */
function give_get_current_setting_section() {
	// Get current tab.
	$current_tab = give_get_current_setting_tab();

	/**
	 * Filter the default section for current setting page tab.
	 *
	 * @since 1.8
	 *
	 * @param string
	 */
	$default_current_section = apply_filters( "give_default_setting_tab_section_{$current_tab}", '' );

	// Get current section.
	$current_section = empty( $_REQUEST['section'] ) ? $default_current_section : urldecode( $_REQUEST['section'] );

	// Output.
	return $current_section;
}

/**
 * Get current setting sub-section.
 *
 * @since  2.6.0
 * @access public
 *
 * @return string
 */
function give_get_current_setting_subsection() {
	// Get current section.
	$current_section = give_get_current_setting_section();

	/**
	 * Filter the default section for current setting page tab.
	 *
	 * @since 2.6.0
	 *
	 * @param string
	 */
	$default_current_subsection = apply_filters( "give_default_setting_tab_subsection_{$current_section}", '' );

	// Get current sub-section.
	$current_subsection = empty( $_REQUEST['subsection'] ) ? $default_current_subsection : urldecode( $_REQUEST['subsection'] );

	// Output.
	return $current_subsection;
}

/**
 * Get current setting page.
 *
 * @since  1.8
 * @return string
 */
function give_get_current_setting_page() {
	// Get current page.
	$setting_page = ! empty( $_GET['page'] ) ? urldecode( $_GET['page'] ) : '';

	// Output.
	return $setting_page;
}
