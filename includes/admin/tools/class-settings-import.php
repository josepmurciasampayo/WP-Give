<?php
/**
 * Give Import Tab
 *
 * @package     Give
 * @subpackage  Classes/Give_Settings_Import
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.8.15
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Give_Settings_Import' ) ) :

	/**
	 * Give_Settings_Import.
	 *
	 * @sine 1.8.15
	 */
	class Give_Settings_Import {

		/**
		 * Setting page id.
		 *
		 * @since 1.8.15
		 * @var   string
		 */
		protected $id = '';

		/**
		 * Setting page label.
		 *
		 * @since 1.8.15
		 * @var   string
		 */
		protected $label = '';

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'import';
			$this->label = __( 'Import', 'give' );

			add_filter( 'give-tools_tabs_array', array( $this, 'add_settings_page' ), 20 );
			add_action( "give-tools_settings_{$this->id}_page", array( $this, 'output' ) );
			add_action( 'give_admin_field_tools_import', array( $this, 'render_import_field' ), 10, 2 );

			// Do not use main donor for this tab.
			if( give_get_current_setting_tab() === $this->id ) {
				add_action( 'give-tools_open_form', '__return_empty_string' );
				add_action( 'give-tools_close_form', '__return_empty_string' );
			}
		}

		/**
		 * Add this page to settings.
		 *
		 * @since  1.8.15
		 * @param  array $pages Lst of pages.
		 * @return array
		 */
		public function add_settings_page( $pages ) {
			$pages[ $this->id ] = $this->label;

			return $pages;
		}

		/**
		 * Get settings array.
		 *
		 * @since  1.8.15
		 * @return array
		 */
		public function get_settings() {
			// Hide save button.
			$GLOBALS['give_hide_save_button'] = true;

			/**
			 * Filter the settings.
			 *
			 * @since  1.8.15
			 * @param  array $settings
			 */
			$settings = apply_filters(
				'give_get_settings_' . $this->id,
				array(
					array(
						'id'   => 'give_tools_import',
						'type' => 'title',
						'table_html' => false
					),
					array(
						'id'   => 'import',
						'name' => __( 'Export', 'give' ),
						'type' => 'tools_import',
					),
					array(
						'id'   => 'give_tools_import',
						'type' => 'sectionend',
						'table_html' => false
					)
				)
			);

			// Output.
			return $settings;
		}

		/**
		 * Output the settings.
		 *
		 * @since  1.8.15
		 * @return void
		 */
		public function output() {
			$settings = $this->get_settings();

			Give_Admin_Settings::output_fields( $settings, 'give_settings' );
		}

		/**
		 * Render report import field
		 *
		 * @since  1.8.15
		 * @access public
		 *
		 * @param $field
		 * @param $option_value
		 */
		public function render_import_field( $field, $option_value ) {
			include_once( 'views/html-admin-page-imports.php' );
		}
	}

endif;

return new Give_Settings_Import();
