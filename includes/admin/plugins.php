<?php
/**
 * Admin Plugins
 *
 * @package     Give
 * @subpackage  Admin/Plugins
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugins row action links
 *
 * @since 1.4
 *
 * @param array $actions An array of plugin action links.
 *
 * @return array An array of updated action links.
 */
function give_plugin_action_links( $actions ) {
	$new_actions = array(
		'settings' => sprintf(
			'<a href="%1$s">%2$s</a>',
			admin_url( 'edit.php?post_type=give_forms&page=give-settings' ),
			__( 'Settings', 'give' )
		),
	);

	return array_merge( $new_actions, $actions );
}

add_filter( 'plugin_action_links_' . GIVE_PLUGIN_BASENAME, 'give_plugin_action_links' );


/**
 * Plugin row meta links
 *
 * @since 1.4
 *
 * @param array  $plugin_meta An array of the plugin's metadata.
 * @param string $plugin_file Path to the plugin file, relative to the plugins directory.
 *
 * @return array
 */
function give_plugin_row_meta( $plugin_meta, $plugin_file ) {
	if ( GIVE_PLUGIN_BASENAME !== $plugin_file ) {
		return $plugin_meta;
	}

	$new_meta_links = array(
		sprintf(
			'<a href="%1$s" target="_blank">%2$s</a>',
			esc_url(
				add_query_arg(
					array(
						'utm_source'   => 'plugins-page',
						'utm_medium'   => 'plugin-row',
						'utm_campaign' => 'admin',
					), 'https://givewp.com/documentation/'
				)
			),
			__( 'Documentation', 'give' )
		),
		sprintf(
			'<a href="%1$s" target="_blank">%2$s</a>',
			esc_url(
				add_query_arg(
					array(
						'utm_source'   => 'plugins-page',
						'utm_medium'   => 'plugin-row',
						'utm_campaign' => 'admin',
					), 'https://givewp.com/addons/'
				)
			),
			__( 'Add-ons', 'give' )
		),
	);

	return array_merge( $plugin_meta, $new_meta_links );
}

add_filter( 'plugin_row_meta', 'give_plugin_row_meta', 10, 2 );


/**
 * Get the Parent Page Menu Title in admin section.
 * Based on get_admin_page_title WordPress Function.
 *
 * @since 1.8.17
 *
 * @global array  $submenu
 * @global string $plugin_page
 *
 * @return string $title Page title
 */
function give_get_admin_page_menu_title() {
	$title = '';
	global $submenu, $plugin_page;

	foreach ( array_keys( $submenu ) as $parent ) {
		if ( 'edit.php?post_type=give_forms' !== $parent ) {
			continue;
		}

		foreach ( $submenu[ $parent ] as $submenu_array ) {
			if ( $plugin_page !== $submenu_array[2] ) {
				continue;
			}

			$title = isset( $submenu_array[0] ) ?
				$submenu_array[0] :
				$submenu_array[3];
		}
	}

	return $title;
}

/**
 * Store recently activated Give's addons to wp options.
 *
 * @since 2.1.0
 */
function give_recently_activated_addons() {
	// Check if action is set.
	if ( isset( $_REQUEST['action'] ) ) {
		$plugin_action = ( '-1' !== $_REQUEST['action'] ) ? $_REQUEST['action'] : ( isset( $_REQUEST['action2'] ) ? $_REQUEST['action2'] : '' );
		$plugins       = array();

		switch ( $plugin_action ) {
			case 'activate': // Single add-on activation.
				$plugins[] = $_REQUEST['plugin'];
				break;
			case 'activate-selected': // If multiple add-ons activated.
				$plugins = $_REQUEST['checked'];
				break;
		}


		if ( ! empty( $plugins ) ) {

			$give_addons = give_get_recently_activated_addons();

			foreach ( $plugins as $plugin ) {
				// Get plugins which has 'Give-' as prefix.
				if ( stripos( $plugin, 'Give-' ) !== false ) {
					$give_addons[] = $plugin;
				}
			}

			if ( ! empty( $give_addons ) ) {
				// Update the Give's activated add-ons.
				update_option( 'give_recently_activated_addons', $give_addons );
			}
		}
	}
}

// Add add-on plugins to wp option table.
add_action( 'activated_plugin', 'give_recently_activated_addons', 10 );

/**
 * Create new menu in plugin section that include all the add-on
 *
 * @since 2.1.0
 *
 * @param $plugin_menu
 *
 * @return mixed
 */
function give_filter_addons_do_filter_addons( $plugin_menu ) {
	global $plugins;

	foreach ( $plugins['all'] as $plugin_slug => $plugin_data ) {

		if ( false !== strpos( $plugin_data['Name'], 'Give' ) && false !== strpos( $plugin_data['AuthorName'], 'WordImpress' ) ) {
			$plugins['give'][ $plugin_slug ]           = $plugins['all'][ $plugin_slug ];
			$plugins['give'][ $plugin_slug ]['plugin'] = $plugin_slug;
			// replicate the next step.
			if ( current_user_can( 'update_plugins' ) ) {
				$current = get_site_transient( 'update_plugins' );
				if ( isset( $current->response[ $plugin_slug ] ) ) {
					$plugins['give'][ $plugin_slug ]['update'] = true;
				}
			}
		}
	}

	return $plugin_menu;

}

add_filter( 'show_advanced_plugins', 'give_filter_addons_do_filter_addons' );
add_filter( 'show_network_active_plugins', 'give_filter_addons_do_filter_addons' );

/**
 * Make the Give Menu as an default menu and update the Menu Name
 *
 * @since 2.1.0
 *
 * @param $views
 *
 * @return mixed
 */
function give_filter_addons_filter_addons( $views ) {

	global $status, $plugins;

	if ( ! empty( $plugins['give'] ) ) {
		$class = '';

		if ( 'give' === $status ) {
			$class = 'current';
		}

		$views['give'] = sprintf(
			'<a class="%s" href="plugins.php?plugin_status=give"> %s <span class="count">(%s) </span></a>',
			$class,
			__( 'Give', 'give' ),
			count( $plugins['give'] )
		);
	}

	return $views;
}

add_filter( 'views_plugins', 'give_filter_addons_filter_addons' );
add_filter( 'views_plugins-network', 'give_filter_addons_filter_addons' );

/**
 * Set the Give as the Main menu when admin click on the Give Menu in Plugin section.
 *
 * @since 2.1.0
 *
 * @param $plugins
 *
 * @return mixed
 */
function give_prepare_filter_addons( $plugins ) {
	global $status;

	if ( isset( $_REQUEST['plugin_status'] ) && 'give' === $_REQUEST['plugin_status'] ) {
		$status = 'give';
	}

	return $plugins;
}

add_filter( 'all_plugins', 'give_prepare_filter_addons' );


/**
 * Display the upgrade notice message.
 *
 * @param array $data     Array of plugin metadata.
 * @param array $response An array of metadata about the available plugin update.
 *
 * @since 2.1
 */
function give_in_plugin_update_message( $data, $response ) {
	$new_version           = $data['new_version'];
	$current_version_parts = explode( '.', GIVE_VERSION );
	$new_version_parts     = explode( '.', $new_version );

	// If it is a minor upgrade then return.
	if ( version_compare( $current_version_parts[0] . '.' . $current_version_parts[1], $new_version_parts[0] . '.' . $new_version_parts[1], '=' ) ) {

		return;
	}

	// Get the upgrade notice from the trunk.
	$upgrade_notice = give_get_plugin_upgrade_notice( $new_version );

	// Display upgrade notice.
	echo apply_filters( 'give_in_plugin_update_message', $upgrade_notice ? '</p>' . wp_kses_post( $upgrade_notice ) . '<p class="dummy">' : '' );
}

// Display upgrade notice.
add_action( 'in_plugin_update_message-' . GIVE_PLUGIN_BASENAME, 'give_in_plugin_update_message', 10, 2 );


/**
 * Get the upgrade notice from WordPress.org.
 *
 * Note: internal purpose use only
 *
 * @since 2.1
 *
 * @param string $new_version New verison of the plugin.
 *
 * @return string
 */
function give_get_plugin_upgrade_notice( $new_version ) {

	// Cache the upgrade notice.
	$transient_name = "give_upgrade_notice_{$new_version}";
	$upgrade_notice = get_transient( $transient_name );

	if ( false === $upgrade_notice ) {
		$response = wp_safe_remote_get( 'https://plugins.svn.wordpress.org/give/trunk/readme.txt' );

		if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) ) {
			$upgrade_notice = give_parse_plugin_update_notice( $response['body'], $new_version );
			set_transient( $transient_name, $upgrade_notice, DAY_IN_SECONDS );
		}
	}

	return $upgrade_notice;
}


/**
 * Parse update notice from readme file.
 *
 * Note: internal purpose use only
 *
 * @since 2.1
 *
 * @param  string $content     Content of the readme.txt file.
 * @param  string $new_version The version with current version is compared.
 *
 * @return string
 */
function give_parse_plugin_update_notice( $content, $new_version ) {
	$version_parts     = explode( '.', $new_version );
	$check_for_notices = array(
		$version_parts[0] . '.0',
		$version_parts[0] . '.0.0',
		$version_parts[0] . '.' . $version_parts[1] . '.' . '0',
	);

	// Regex to extract Upgrade notice from the readme.txt file.
	$notice_regexp = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( $new_version ) . '\s*=|$)~Uis';

	$upgrade_notice = '';

	foreach ( $check_for_notices as $check_version ) {
		if ( version_compare( GIVE_VERSION, $check_version, '>' ) ) {
			continue;
		}

		$matches = null;

		if ( preg_match( $notice_regexp, $content, $matches ) ) {
			$notices = (array) preg_split( '~[\r\n]+~', trim( $matches[2] ) );

			if ( version_compare( trim( $matches[1] ), $check_version, '=' ) ) {
				$upgrade_notice .= '<p class="give-plugin-upgrade-notice">';

				foreach ( $notices as $index => $line ) {
					$upgrade_notice .= preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $line );
				}

				$upgrade_notice .= '</p>';
			}

			if ( ! empty( $upgrade_notice ) ) {
				break;
			}
		}
	}

	return wp_kses_post( $upgrade_notice );
}


/**
 * Add styling to the plugin upgrade notice.
 *
 * @since 2.1
 */
function give_plugin_notice_css() {
?>
	<style type="text/css">
	#give-update .give-plugin-upgrade-notice {
		font-weight: 400;
		background: #fff8e5!important;
		border-left: 4px solid #ffb900;
		border-top: 1px solid #ffb900;
		padding: 9px 0 9px 12px!important;
		margin: 0 -12px 0 -16px!important;
	}

	#give-update .give-plugin-upgrade-notice:before {
		content: '\f348';
		display: inline-block;
		font: 400 18px/1 dashicons;
		speak: none;
		margin: 0 8px 0 -2px;
		vertical-align: top;
	}

	#give-update .dummy {
		display: none;
	}
	</style>
<?php
}

add_action( 'admin_head', 'give_plugin_notice_css' );

/**
 * Get list of add-on last activated.
 *
 * @since 2.1.3
 *
 * @return mixed|array list of recently activated add-on
 */
function give_get_recently_activated_addons() {
	return get_option( 'give_recently_activated_addons', array() );
}

/**
 * Renders the Give Deactivation Survey Form.
 *
 * @since 2.2
 */
function give_deactivation_popup() {
	ob_start();
	?>

	<h2 id="deactivation-survey-title">
		<img src="<?php echo esc_url( GIVE_PLUGIN_URL ) ?>/assets/dist/images/give-icon-full-circle.svg">
		<span><?php esc_html_e( 'Give Deactivation', 'give' ); ?></span>
	</h2>
	<form class="deactivation-survey-form" method="POST">
		<p><?php esc_html_e( 'If you have a moment, please let us know why you are deactivating Give. All submissions are anonymous and we only use this feedback to improve this plugin.', 'give' ); ?></p>

		<div>
			<label class="give-field-description">
				<input type="radio" name="give-survey-radios" value="1">
				<?php esc_html_e( "I'm only deactivating temporarily", 'give' ); ?>
			</label>
		</div>

		<div>
			<label class="give-field-description">
				<input type="radio" name="give-survey-radios" value="2">
				<?php esc_html_e( 'I no longer need the plugin', 'give' ); ?>
			</label>
		</div>

		<div>
			<label class="give-field-description">
				<input type="radio" name="give-survey-radios" value="3" data-has-field="true">
				<?php esc_html_e( 'I found a better plugin', 'give' ); ?>
			</label>

			<div class="give-survey-extra-field">
				<p><?php esc_html_e( 'What is the name of the plugin?', 'give' ); ?></p>
				<input type="text" name="user-reason" class="widefat">
			</div>
		</div>

		<div>
			<label class="give-field-description">
				<input type="radio" name="give-survey-radios" value="4">
				<?php esc_html_e( 'I only needed the plugin for a short period', 'give' ); ?>
			</label>
		</div>

		<div>
			<label class="give-field-description">
				<input type="radio" name="give-survey-radios" value="5" data-has-field="true">
				<?php esc_html_e( 'The plugin broke my site', 'give' ); ?>
			</label>

			<div class="give-survey-extra-field">
				<p><?php
					printf(
						'%1$s %2$s %3$s',
						__( "We're sorry to hear that, check", 'give' ),
						'<a href="https://wordpress.org/support/plugin/give">Give Support</a>.',
						__( 'Can you describe the issue?', 'give' )
					);
					?>
				</p>
				<textarea disabled name="user-reason" class="widefat" rows="6"></textarea disabled>
			</div>
		</div>

		<div>
			<label class="give-field-description">
				<input type="radio" name="give-survey-radios" value="6" data-has-field="true">
				<?php esc_html_e( 'The plugin suddenly stopped working', 'give' ); ?>
			</label>

			<div class="give-survey-extra-field">
				<p><?php
					printf(
						'%1$s %2$s %3$s',
						__( "We're sorry to hear that, check", 'give' ),
						'<a href="https://wordpress.org/support/plugin/give">Give Support</a>.',
						__( 'Can you describe the issue?', 'give' )
					);
					?>
				</p>
				<textarea disabled name="user-reason" class="widefat" rows="6"></textarea disabled>
			</div>
		</div>

		<div>
			<label class="give-field-description">
				<input type="radio" name="give-survey-radios" value="7" data-has-field="true">
				<?php esc_html_e( 'Other', 'give' ); ?>
			</label>

			<div class="give-survey-extra-field">
				<p><?php esc_html_e( "Please describe why you're deactivating Give", 'give' ); ?></p>
				<textarea disabled name="user-reason" class="widefat" rows="6"></textarea disabled>
			</div>
		</div>

		<div id="survey-and-delete-data">
			<p>
				<label>
					<input type="checkbox" name="confirm_reset_store" value="1">
					<?php esc_html_e( 'Would you like to delete all Give data?', 'give' ); ?>
				</label>
				<section class="give-field-description">
					<?php esc_html_e( 'By default the custom roles, Give options, and database entries are not deleted when you deactivate Give. If you are deleting Give completely from your website and want those items removed as well check this option. Note: This will permanently delete all Give data from your database.', 'give' ); ?>
				</section>
			</p>
		</div>
		<?php
		$current_user       = wp_get_current_user();
		$current_user_email = $current_user->user_email;
		?>
		<input type="hidden" name="current-user-email" value="<?php echo $current_user_email; ?>">
		<input type="hidden" name="current-site-url" value="<?php echo esc_url( get_bloginfo( 'url' ) ); ?>">
		<input type="hidden" name="give-export-class" value="Give_Tools_Reset_Stats">
		<?php wp_nonce_field( 'give_ajax_export', 'give_ajax_export' ); ?>
	</form>

	<?php
	echo ob_get_clean();

	wp_die();
}

add_action( 'wp_ajax_deactivation_popup', 'give_deactivation_popup' );

/**
 * Ajax callback after the deactivation survey form has been submitted.
 *
 * @since 2.2
 */
function give_deactivation_form_submit() {

	if ( ! check_ajax_referer( 'deactivation_survey_nonce', 'nonce', false ) ) {
		wp_send_json_error();
		wp_die();
	}

	$form_data   = give_clean( wp_parse_args( $_POST['form-data'] ) );

	// Get the selected radio value.
	$radio_value = isset( $form_data['give-survey-radios'] ) ? $form_data['give-survey-radios'] : 0;

	// Get the reason if any radio button has an optional text field.
	$user_reason = isset( $form_data['user-reason'] ) ? $form_data['user-reason'] : '';

	// Get the email of the user who deactivated the plugin.
	$user_email  = isset( $form_data['current-user-email'] ) ? $form_data['current-user-email'] : '';

	// Get the URL of the website on which Give plugin is being deactivated.
	$site_url    = isset( $form_data['current-site-url'] ) ? $form_data['current-site-url'] : '';

	// Get the value of the checkbox for deleting Give's data.
	$delete_data = isset( $form_data['confirm_reset_store'] ) ? $form_data['confirm_reset_store'] : '';

	/**
	 * Make a POST request to the endpoint to send the survey data.
	 */
	$response    = wp_remote_post(
		'http://give.survey/wp-json/give/v2/survey/',
		array(
			'body' => array(
				'radio_value'        => $radio_value,
				'user_reason'        => $user_reason,
				'current_user_email' => $user_email,
				'site_url'           => $site_url,
			)
		)
	);

	// Check if the data is sent and stored correctly.
	$response = wp_remote_retrieve_body( $response );

	if ( 'true' === $response ) {
		if ( '1' === $delete_data ) {
			wp_send_json_success(
				array(
					'delete_data' => true,
				)
			);
		} else {
			wp_send_json_success(
				array(
					'delete_data' => false,
				)
			);
		}
	} else {
		wp_send_json_error();
	}

	wp_die();
}

add_action( 'wp_ajax_deactivation_form_submit', 'give_deactivation_form_submit' );