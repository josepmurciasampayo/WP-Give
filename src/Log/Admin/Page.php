<?php

namespace Give\Log\Admin;

/**
 * Class Page
 * @package Give\Log\Admin
 *
 * @since 2.9.7
 */
class Page {

	const SLUG = 'give-logs';

	/**
	 * Register admin page
	 */
	public function register() {
		add_submenu_page(
			'edit.php?post_type=give_forms',
			esc_html__( 'Logs', 'give' ),
			esc_html__( 'Logs', 'give' ),
			'manage_options',
			self::SLUG,
			[ $this, 'renderContainer' ],
			5
		);
	}

	/**
	 * Render app container
	 */
	public function renderContainer() {
		echo '<div id="logs-list-table-app"></div>';
	}
}
