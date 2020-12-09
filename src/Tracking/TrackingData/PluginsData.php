<?php
namespace Give\Tracking\TrackingData;

use Give\Tracking\Contracts\TrackData;
use Give\License\PremiumAddonsListManager;

/**
 * Class ActivePluginsData
 *
 * Represents the plugin data.
 *
 * @since 2.10.0
 * @package Give\Tracking\TrackingData
 */
class PluginsData implements TrackData {

	/**
	 * Returns the collection data.
	 *
	 * @since 2.10.0
	 *
	 * @return array The collection data.
	 */
	public function get() {
		return [
			'plugins' => $this->getPluginData(),
		];
	}

	/**
	 * Returns all plugins.
	 *
	 * @since 2.10.0
	 *
	 * @return array The formatted plugins.
	 */
	private function getPluginData() {
		$plugins = give_get_plugins();
		$plugins = array_map( [ $this, 'formatPlugin' ], $plugins );

		$plugin_data = [];
		foreach ( $plugins as $plugin ) {
			$plugin_data[] = $plugin;
		}

		return $plugin_data;
	}

	/**
	 * Formats the plugin array.
	 *
	 * @since 2.10.0
	 *
	 * @param  array  $plugin  The plugin details.
	 *
	 * @return array The formatted array.
	 */
	private function formatPlugin( $plugin ) {
		$premiumAddonsListManger = new PremiumAddonsListManager();

		return [
			'name'      => $plugin['Name'],
			'url'       => $plugin['PluginURI'],
			'slug'      => $plugin['Dir'],
			'version'   => $plugin['Version'],
			'status'    => $plugin['Status'],
			'type'      => $plugin['Type'],
			'author'    => [
				'name' => $plugin['Author'],
				'url'  => $plugin['AuthorURI'],
			],
			'isPremium' => absint( $premiumAddonsListManger->isPremiumAddons( $plugin['PluginURI'] ) ),
		];
	}
}

