<?php
/**
 * Give Settings Page/Tab
 *
 * @package     Give
 * @subpackage  Classes/Give_Settings_Tools
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'Give_Settings_Tools' ) ) :

	/**
	 * Give_Settings_Tools.
	 *
	 * @sine 1.8
	 */
	class Give_Settings_Tools {

		/**
		 * Setting page id.
		 *
		 * @since 1.8
		 * @var   string
		 */
		protected $id = '';

		/**
		 * Setting page label.
		 *
		 * @since 1.8
		 * @var   string
		 */
		protected $label = '';

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'tools';
			$this->label = esc_html__( 'Tools', 'give' );

			add_filter( 'give_default_setting_tab_section_tools', array( $this, 'set_default_setting_tab' ), 10 );
			add_filter( 'give-settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
			add_action( "give-settings_sections_{$this->id}_page", array( $this, 'output_sections' ) );
			add_action( "give-settings_settings_{$this->id}_page", array( $this, 'output' ) );
			add_action( "give-settings_save_{$this->id}", array( $this, 'save' ) );
		}

		/**
		 * Default setting tab.
		 *
		 * @since  1.8
		 * @param  $setting_tab
		 * @return string
		 */
		function set_default_setting_tab( $setting_tab ) {
			return 'api';
		}

		/**
		 * Add this page to settings.
		 *
		 * @since  1.8
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
		 * @since  1.8
		 * @return array
		 */
		public function get_settings() {
			$settings = array();
			$current_section = give_get_current_setting_section();

			switch ( $current_section ) {
				case 'api' :
					// Hide save button.
					$GLOBALS['give_hide_save_button'] = true;

					$settings = apply_filters( 'give_settings_system', array(
						array(
							'id'   => 'give_title_tools_settings_1',
							'type' => 'title'
						),
						array(
							'id'   => 'api',
							'name' => esc_html__( 'API', 'give' ),
							'type' => 'api'
						),
						array(
							'id'   => 'give_title_tools_settings_1',
							'type' => 'sectionend'
						),
					));
					break;

				case 'system-info':
					// Hide save button.
					$GLOBALS['give_hide_save_button'] = true;
					
					$settings = apply_filters( 'give_settings_system', array(
						array(
							'id'   => 'give_title_tools_settings_2',
							'type' => 'title'
						),
						array(
							'id'   => 'system-info-textarea',
							'name' => esc_html__( 'System Info', 'give' ),
							'desc' => esc_html__( 'Please copy and paste this information in your ticket when contacting support.', 'give' ),
							'type' => 'system_info'
						),
						array(
							'id'   => 'give_title_advanced_settings_2',
							'type' => 'sectionend'
						)
					));
					break;
			}

			/**
			 * Filter the settings.
			 *
			 * @since  1.8
			 * @param  array $settings
			 */
			$settings = apply_filters( 'give_get_settings_' . $this->id, $settings );

			// Output.
			return $settings;
		}

		/**
		 * Get sections.
		 *
		 * @since 1.8
		 * @return array
		 */
		public function get_sections() {
			$sections = array(
				'api'         => esc_html__( 'API', 'give' ),
				'system-info' => esc_html__( 'System Info', 'give' )
			);

			return apply_filters( 'give_get_sections_' . $this->id, $sections );
		}

		/**
		 * Output sections.
		 *
		 * @since  1.8
		 * @return void
		 */
		public function output_sections() {
			// Get current section.
			$current_section = give_get_current_setting_section();

			// Get all sections.
			$sections = $this->get_sections();

			if ( empty( $sections ) || 1 === sizeof( $sections ) ) {
				return;
			}

			echo '<ul class="subsubsub">';

			// Get section keys.
			$array_keys = array_keys( $sections );

			foreach ( $sections as $id => $label ) {
				echo '<li><a href="' . admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';
			}

			echo '</ul><br class="clear" />';
		}

		/**
		 * Output the settings.
		 *
		 * @since  1.8
		 * @return void
		 */
		public function output() {
			$settings = $this->get_settings();

			Give_Admin_Settings::output_fields( $settings, 'give_settings' );
		}

		/**
		 * Save settings.
		 *
		 * @since  1.8
		 * @return void
		 */
		public function save() {
			$settings = $this->get_settings();

			Give_Admin_Settings::save_fields( $settings, 'give_settings' );
		}
	}

endif;

return new Give_Settings_Tools();
