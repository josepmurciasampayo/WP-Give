<?php
/**
 * Admin Filters
 *
 * @package     Give
 * @subpackage  Admin/Filters
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize number of decimals setting field.
 *
 *  1. User can only set absolute integer value as number of decimals.
 *  2. number_decimals setting will be zero if no decimal separator defined
 *
 * @since   1.8
 * @used-by Give_Plugin_Settings::give_settings()
 *
 * @param   string $value
 *
 * @return  mixed
 */
function __give_sanitize_number_decimals_setting_field( $value ) {
	$value_changed = false;
	$old_value     = $value;

	if ( isset( $_POST['decimal_separator'] ) ) {
		$value         = ! empty( $_POST['decimal_separator'] ) ? $value : 0;
		$value_changed = true;
	}

	if ( $value_changed && ( $old_value != $value ) ) {
		Give_Admin_Settings::add_error( 'give-number-decimal', __( 'The \'Number of Decimals\' option has been automatically set to zero because the \'Decimal Separator\' is not set.', 'give' ) );
	}

	$value = absint( $value );

	if( 6 <= $value ) {
		$value = 5;
		Give_Admin_Settings::add_error( 'give-number-decimal', __( 'The \'Number of Decimals\' option has been automatically set to 5 because you entered a number higher than the maximum allowed.', 'give' ) );
	}

	return absint( $value );
}

add_filter( 'give_admin_settings_sanitize_option_number_decimals', '__give_sanitize_number_decimals_setting_field', 10 );


/**
 * Sanitize number of decimals setting field.
 *
 *  1. User can only set absolute integer value as number of decimals.
 *  2. number_decimals setting will be zero if no decimal separator defined
 *
 * @since   1.8
 * @used-by Give_Plugin_Settings::give_settings()
 *
 * @param   string $value
 *
 * @return  mixed
 */
function __give_validate_decimal_separator_setting_field( $value ) {
	$thousand_separator = give_clean( $_POST['thousands_separator'] );
	$decimal_separator  = give_clean( $_POST['decimal_separator'] );

	if ( $decimal_separator === $thousand_separator ) {
		$value                    = '';
		$_POST['number_decimals'] = 0;
		Give_Admin_Settings::add_error( 'give-decimal-separator', __( 'The \'Decimal Separator\' option has automatically been set to empty because it can not be equal to the \'Thousand Separator\'', 'give' ) );
	}

	return $value;
}

add_filter( 'give_admin_settings_sanitize_option_decimal_separator', '__give_validate_decimal_separator_setting_field', 10 );

/**
 * Change $delimiter text to symbol.
 *
 * @since 1.8.14
 *
 * @param string $delimiter.
 *
 * @return string $delimiter.
 */
function __give_import_delimiter_set_callback( $delimiter ) {
	$delimite_type = array(
		'csv'                  => ",",
		'tab-separated-values' => "\t",
	);

	return ( array_key_exists( $delimiter, $delimite_type ) ? $delimite_type[ $delimiter ] : "," );
}

add_filter( 'give_import_delimiter_set', '__give_import_delimiter_set_callback', 10 );

/**
 * Give unset the page id from the core setting data from the json files.
 *
 * @since 1.8.17
 *
 * @param array $json_to_array Data from json file
 * @param string $type
 *
 * @return array $json_to_array
 */
function give_import_core_settings_merge_pages( $json_to_array, $type ) {
	if ( 'merge' === $type ) {
		unset( $json_to_array['success_page'] );
		unset( $json_to_array['failure_page'] );
		unset( $json_to_array['history_page'] );
	}

	return $json_to_array;
}

add_filter( 'give_import_core_settings_data', 'give_import_core_settings_merge_pages', 11, 2 );

/**
 * Give check the image size from the core setting data from the json files.
 *
 * @since 1.8.17
 *
 * @param $json_to_array
 * @param string $type
 *
 * @return array $json_to_array
 */
function give_import_core_settings_merge_image_size( $json_to_array, $type ) {
	if ( 'merge' === $type ) {
		// Featured image sizes import under Display Options > Post Types > Featured Image Size.
		if ( 'enabled' === $json_to_array['form_featured_img'] ) {
			$images_sizes = get_intermediate_image_sizes();

			if ( ! in_array( $json_to_array['featured_image_size'], $images_sizes ) ) {
				unset( $json_to_array['featured_image_size'] );
			}
		}
	}

	return $json_to_array;
}

add_filter( 'give_import_core_settings_data', 'give_import_core_settings_merge_image_size', 12, 2 );

/**
 * Give upload the image logo from the core setting data from the json files.
 *
 * @since 1.8.17
 *
 * @param $json_to_array
 * @param string $type
 *
 * @return array $json_to_array
 */
function give_import_core_settings_merge_upload_image( $json_to_array, $type ) {
	if ( 'merge' === $type ) {
		// Emails > Email Settings > Logo.
		if ( ! empty( $json_to_array['email_logo'] ) ) {

			// Need to require these files.
			if ( ! function_exists( 'media_handle_upload' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				require_once( ABSPATH . 'wp-admin/includes/media.php' );
			}

			$url     = $json_to_array['email_logo'];
			$new_url = media_sideload_image( $url, 0, null, 'src' );
			if ( ! is_wp_error( $new_url ) ) {
				$json_to_array['email_logo'] = $new_url;
			} else {
				unset( $json_to_array['email_logo'] );
			}
		}
	}

	return $json_to_array;
}

add_filter( 'give_import_core_settings_data', 'give_import_core_settings_merge_upload_image', 13, 2 );

/**
 * Give unset the license key from the core setting data from the json files.
 *
 * @since 1.8.17
 *
 * @param array $json_to_array Data from json file
 * @param string $type
 *
 * @return array $json_to_array
 */
function give_import_core_settings_merge_license_key( $json_to_array, $type ) {
	if ( 'merge' === $type ) {
		foreach ( $json_to_array as $key => $value ) {
			$is_license_key = strpos( '_license_key', $key );
			if ( ! empty( $is_license_key ) ) {
				unset( $json_to_array[ $key ] );
			}
		}
	}

	return $json_to_array;
}

add_filter( 'give_import_core_settings_data', 'give_import_core_settings_merge_license_key', 14, 2 );

/**
 * Give merge the json data and setting data.
 *
 * @since 1.8.17
 *
 * @param $json_to_array
 * @param $type
 * @param $host_give_options
 *
 * @return array $json_to_array
 */
function give_import_core_settings_merge_data( $json_to_array, $type, $host_give_options ) {
	if ( 'merge' === $type ) {
		$json_to_array_merge = array_merge( $host_give_options, $json_to_array );
		$json_to_array       = $json_to_array_merge;
	}

	return $json_to_array;
}

add_filter( 'give_import_core_settings_data', 'give_import_core_settings_merge_data', 1000, 3 );

/**
 * Backward Compatibility - Cleanup User Roles.
 *
 * @param array $caps List of capabilities.
 *
 * @since 1.8.17
 *
 * @return mixed
 */
function give_bc_1817_cleanup_user_roles( $caps ){

	if (
		! give_has_upgrade_completed( 'v1817_cleanup_user_roles' ) &&
		! isset( $caps['view_give_payments'] ) &&
		current_user_can( 'manage_options' )
	) {
		$caps['view_give_payments'] = true;
	}

	return $caps;
}

add_filter( 'user_has_cap', 'give_bc_1817_cleanup_user_roles' );