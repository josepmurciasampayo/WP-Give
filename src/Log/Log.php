<?php

namespace Give\Log;

use InvalidArgumentException;
use Give\Log\ValueObjects\LogCategory;
use Give\Framework\Migrations\Contracts\Migration;

/**
 * Class Log
 *
 * The static facade intended to be the primary way of logging within GiveWP to make life easier.
 *
 * @package Give\Log
 * @since 2.9.7
 *
 * @method static error( string $message, array $context = [] )
 * @method static warning( string $message, array $context = [] )
 * @method static notice( string $message, array $context = [] )
 * @method static success( string $message, array $context = [] )
 * @method static info( string $message, array $context = [] )
 * @method static http( string $message, array $context = [] )
 */
class Log {
	/**
	 * @param  string  $type
	 * @param  array  $args
	 */
	public static function __callStatic( $type, $args ) {
		$data = [];

		list ( $message, $context ) = array_pad( $args, 2, null );

		if ( is_array( $context ) ) {
			// Convert context values to string
			$context = array_map(
				function( $item ) {
					if ( is_array( $item ) || is_object( $item ) ) {
						  $item = print_r( $item, true );
					}
					return $item;
				},
				$context
			);

			// Default fields
			$data = array_filter(
				$context,
				function( $key ) {
					return array_key_exists( $key, LogFactory::getDefaults() );
				},
				ARRAY_FILTER_USE_KEY
			);

			// Additional context
			$data['context'] = array_diff(
				$context,
				$data
			);
		}

		// Set message
		if ( ! is_null( $message ) ) {
			$data['message'] = $message;
		}

		LogFactory::makeFromArray( $data )->save();
	}
}
