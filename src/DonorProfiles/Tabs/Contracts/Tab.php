<?php

namespace Give\DonorProfiles\Tabs\Contracts;

use RuntimeException;
use Give\DonorProfiles\Tabs\Contracts\Route as RouteAbstract;

/**
 * Class Tab
 *
 * Extend this class when creating Donor Profile tabs.
 *
 * @since 2.11.0
 */
abstract class Tab {
	/**
	 * Return array of routes (must extend DonorProfile Route class)
	 *
	 * @return array
	 * @since 2.11.0
	 */
	abstract public function routes();

	/**
	 * Return a unique identifier for the tab
	 *
	 * @return string
	 * @since 2.11.0
	 */
	public static function id() {
		throw new RuntimeException( 'A unique ID must be provided for the tab' );
	}


	/**
	 * Enqueue assets required for frontend rendering of tab
	 *
	 * @since 2.11.0
	 */
	public function enqueueAssets() {
		return null;
	}

	/**
	 * Registers routes with WP REST api
	 *
	 * @since 2.11.0
	 */
	public function registerRoutes() {
		$routeClasses = $this->routes();
		foreach ( $routeClasses as $routeClass ) {
			if ( ! is_subclass_of( $routeClass, RouteAbstract::class ) ) {
				throw new \InvalidArgumentException( 'Class must extend the ' . RouteAbstract::class . ' class' );
			}
			( new $routeClass )->registerRoute();
		}
	}

	public function registerTab() {
		give()->donorProfileTabs->addTab( get_called_class() );
	}
}
