<?php
/**
 * Class for logging events and errors
 *
 * @package     Give
 * @subpackage  Classes/Give_Logging
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Give_Logging Class
 *
 * A general use class for logging events and errors.
 *
 * @since 1.0
 */
class Give_Logging {
	/**
	 * Logs data operation handler object.
	 *
	 * @since  2.0
	 * @access private
	 * @var Give_DB_Logs
	 */
	public $log_db;

	/**
	 * Log meta data operation handler object.
	 *
	 * @since  2.0
	 * @access private
	 * @var Give_DB_Log_Meta
	 */
	public $logmeta_db;

	/**
	 * Class Constructor
	 *
	 * Set up the Give Logging Class.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function __construct() {
		/**
		 * Setup properties
		 */

		require_once GIVE_PLUGIN_DIR . 'includes/class-give-db-logs.php';
		require_once GIVE_PLUGIN_DIR . 'includes/class-give-db-logs-meta.php';
		$this->log_db     = new Give_DB_Logs();
		$this->logmeta_db = new Give_DB_Log_Meta();

		/**
		 * Setup hooks.
		 */

		add_action( 'save_post_give_payment', array( $this, 'background_process_delete_cache' ) );
		add_action( 'save_post_give_forms', array( $this, 'background_process_delete_cache' ) );
		add_action( 'save_post_give_log', array( $this, 'background_process_delete_cache' ) );
		add_action( 'give_delete_log_cache', array( $this, 'delete_cache' ) );

		// Backward compatibility.
		if ( ! give_has_upgrade_completed( 'give_v20_logs_upgrades' ) ) {
			// Create the log post type
			add_action( 'init', array( $this, 'register_post_type' ), 1 );

			// Create types taxonomy and default types
			add_action( 'init', array( $this, 'register_taxonomy' ), 1 );
		}
	}


	/**
	 * Log Post Type
	 *
	 * Registers the 'give_log' Post Type.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_post_type() {
		/* Logs post type */
		$log_args = array(
			'labels'              => array(
				'name' => esc_html__( 'Logs', 'give' ),
			),
			'public'              => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_ui'             => false,
			'query_var'           => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'supports'            => array( 'title', 'editor' ),
			'can_export'          => true,
		);

		register_post_type( 'give_log', $log_args );
	}

	/**
	 * Log Type Taxonomy
	 *
	 * Registers the "Log Type" taxonomy.  Used to determine the type of log entry.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return void
	 */
	public function register_taxonomy() {
		register_taxonomy( 'give_log_type', 'give_log', array(
			'public' => false,
		) );
	}

	/**
	 * Log Types
	 *
	 * Sets up the default log types and allows for new ones to be created.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array $terms
	 */
	public function log_types() {
		$terms = array(
			'sale',
			'gateway_error',
			'api_request',
		);

		return apply_filters( 'give_log_types', $terms );
	}

	/**
	 * Check if a log type is valid
	 *
	 * Checks to see if the specified type is in the registered list of types.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  string $type Log type.
	 *
	 * @return bool         Whether log type is valid.
	 */
	public function valid_type( $type ) {
		return in_array( $type, $this->log_types() );
	}

	/**
	 * Create new log entry
	 *
	 * This is just a simple and fast way to log something. Use $this->insert_log()
	 * if you need to store custom meta data.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  string $title   Log entry title. Default is empty.
	 * @param  string $message Log entry message. Default is empty.
	 * @param  int    $parent  Log entry parent. Default is 0.
	 * @param  string $type    Log type. Default is empty string.
	 *
	 * @return int             Log ID.
	 */
	public function add( $title = '', $message = '', $parent = 0, $type = '' ) {
		$log_data = array(
			'post_title'   => $title,
			'post_content' => $message,
			'post_parent'  => $parent,
			'log_type'     => $type,
		);

		return $this->insert_log( $log_data );
	}

	/**
	 * Get Logs
	 *
	 * Retrieves log items for a particular object ID.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  int    $object_id Log object ID. Default is 0.
	 * @param  string $type      Log type. Default is empty string.
	 * @param  int    $paged     Page number Default is null.
	 *
	 * @return array             An array of the connected logs.
	 */
	public function get_logs( $object_id = 0, $type = '', $paged = null ) {
		return $this->get_connected_logs( array(
			'log_parent' => $object_id,
			'paged'      => $paged,
			'log_type'   => $type,
		) );
	}

	/**
	 * Stores a log entry
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  array $log_data Log entry data.
	 * @param  array $log_meta Log entry meta.
	 *
	 * @return int             The ID of the newly created log item.
	 */
	public function insert_log( $log_data = array(), $log_meta = array() ) {
		$log_id = 0;

		$defaults = array(
			'log_parent'  => 0,
			'log_content' => '',
			'log_type'    => false,

			// Backward compatibility.
			'post_type'   => 'give_log',
			'post_status' => 'publish',
		);

		$args = wp_parse_args( $log_data, $defaults );
		$this->bc_200_validate_params( $args, $log_meta );

		/**
		 * Fires before inserting log entry.
		 *
		 * @since 1.0
		 *
		 * @param array $log_data Log entry data.
		 * @param array $log_meta Log entry meta.
		 */
		do_action( 'give_pre_insert_log', $log_data, $log_meta );

		if ( ! give_has_upgrade_completed( 'give_v20_logs_upgrades' ) ) {
			global $wpdb;

			// Backward Compatibility.
			if ( ! $wpdb->get_var( "SELECT ID from {$this->log_db->table_name} ORDER BY id DESC LIMIT 1" ) ) {
				$latest_log_id = $wpdb->get_var( "SELECT ID from $wpdb->posts ORDER BY id DESC LIMIT 1" );
				$latest_log_id = empty( $latest_log_id ) ? 1 : ++ $latest_log_id;

				$args['ID'] = $latest_log_id;
				$this->log_db->insert( $args );
			}
		}

		$log_id = $this->log_db->add( $args );

		// Set log meta, if any
		if ( $log_id && ! empty( $log_meta ) ) {
			foreach ( (array) $log_meta as $key => $meta ) {
				$this->logmeta_db->update_meta( $log_id, '_give_log_' . sanitize_key( $key ), $meta );
			}
		}

		/**
		 * Fires after inserting log entry.
		 *
		 * @since 1.0
		 *
		 * @param int   $log_id   Log entry id.
		 * @param array $log_data Log entry data.
		 * @param array $log_meta Log entry meta.
		 */
		do_action( 'give_post_insert_log', $log_id, $log_data, $log_meta );

		// Delete cache.
		$this->delete_cache();

		return $log_id;
	}

	/**
	 * Update and existing log item
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  array $log_data Log entry data.
	 * @param  array $log_meta Log entry meta.
	 *
	 * @return bool|null       True if successful, false otherwise.
	 */
	public function update_log( $log_data = array(), $log_meta = array() ) {
		$log_id = 0;

		/**
		 * Fires before updating log entry.
		 *
		 * @since 1.0
		 *
		 * @param array $log_data Log entry data.
		 * @param array $log_meta Log entry meta.
		 */
		do_action( 'give_pre_update_log', $log_data, $log_meta );

		$defaults = array(
			'log_parent'  => 0,

			// Backward compatibility.
			'post_type'   => 'give_log',
			'post_status' => 'publish',
		);

		$args = wp_parse_args( $log_data, $defaults );
		$this->bc_200_validate_params( $args, $log_meta );

		// Store the log entry
		if ( ! give_has_upgrade_completed( 'give_v20_logs_upgrades' ) ) {
			// Backward compatibility.
			$log_id = wp_update_post( $args );

			if ( $log_id && ! empty( $log_meta ) ) {
				foreach ( (array) $log_meta as $key => $meta ) {
					if ( ! empty( $meta ) ) {
						give_update_meta( $log_id, '_give_log_' . sanitize_key( $key ), $meta );
					}
				}
			}
		} else {
			$log_id = $this->log_db->add( $args );

			if ( $log_id && ! empty( $log_meta ) ) {
				foreach ( (array) $log_meta as $key => $meta ) {
					if ( ! empty( $meta ) ) {
						$this->logmeta_db->update_meta( $log_id, '_give_log_' . sanitize_key( $key ), $meta );
					}
				}
			}
		}

		/**
		 * Fires after updating log entry.
		 *
		 * @since 1.0
		 *
		 * @param int   $log_id   Log entry id.
		 * @param array $log_data Log entry data.
		 * @param array $log_meta Log entry meta.
		 */
		do_action( 'give_post_update_log', $log_id, $log_data, $log_meta );
	}

	/**
	 * Retrieve all connected logs
	 *
	 * Used for retrieving logs related to particular items, such as a specific donation.
	 * For new table params check: Give_DB_Logs::get_column_defaults and Give_DB_Logs::get_sql#L262
	 *
	 * @since  1.0
	 * @since  2.0 Added new table logic.
	 * @access public
	 *
	 * @param  array $args Query arguments.
	 *
	 * @return array|false Array if logs were found, false otherwise.
	 */
	public function get_connected_logs( $args = array() ) {
		$logs = array();

		$defaults   = array(
			'number'      => 20,
			'paged'       => get_query_var( 'paged' ),
			'log_type'    => false,

			// Backward compatibility.
			'post_type'   => 'give_log',
			'post_status' => 'publish',
		);
		$query_args = wp_parse_args( $args, $defaults );
		$this->bc_200_validate_params( $query_args );

		if ( ! give_has_upgrade_completed( 'give_v20_logs_upgrades' ) ) {
			// Backward compatibility.
			$logs = get_posts( $query_args );
			$this->bc_200_add_new_properties( $logs );
		} else {
			$logs = $this->log_db->get_logs( $query_args );
		}

		return ( ! empty( $logs ) ? $logs : false );
	}

	/**
	 * Retrieve Log Count
	 *
	 * Retrieves number of log entries connected to particular object ID.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  int    $object_id  Log object ID. Default is 0.
	 * @param  string $type       Log type. Default is empty string.
	 * @param  array  $meta_query Log meta query. Default is null.
	 * @param  array  $date_query Log data query. Default is null.
	 *
	 * @return int                Log count.
	 */
	public function get_log_count( $object_id = 0, $type = '', $meta_query = null, $date_query = null ) {
		$logs_count = 0;

		$query_args = array(
			'number'      => - 1,
			'fields'      => 'ids',

			// Backward comatibility.
			'post_type'   => 'give_log',
			'post_status' => 'publish',
		);

		if ( $object_id ) {
			$query_args['log_parent'] = $object_id;
		}

		if ( ! empty( $type ) && $this->valid_type( $type ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'give_log_type',
					'field'    => 'slug',
					'terms'    => $type,
				),
			);
		}

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		if ( ! empty( $date_query ) ) {
			$query_args['date_query'] = $date_query;
		}

		$this->bc_200_validate_params( $query_args );

		// Get cache key for current query.
		$cache_key = Give_Cache::get_key( 'get_log_count', $query_args );

		// check if cache already exist or not.
		if ( ! ( $logs_count = Give_Cache::get( $cache_key ) ) ) {
			if ( ! give_has_upgrade_completed( 'give_v20_logs_upgrades' ) ) {
				// Backward compatibility.
				$logs       = new WP_Query( $query_args );
				$logs_count = (int) $logs->post_count;
			} else {
				$logs_count = $this->log_db->count( $query_args );
			}

			// Cache results.
			Give_Cache::set( $cache_key, $logs_count );
		}

		return $logs_count;
	}

	/**
	 * Delete Logs
	 *
	 * Remove log entries connected to particular object ID.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param  int    $object_id  Log object ID. Default is 0.
	 * @param  string $type       Log type. Default is empty string.
	 * @param  array  $meta_query Log meta query. Default is null.
	 *
	 * @return void
	 */
	public function delete_logs( $object_id = 0, $type = '', $meta_query = null ) {
		$query_args = array(
			'log_parent'  => $object_id,
			'number'      => - 1,
			'fields'      => 'ids',

			// Backward compatibility.
			'post_type'   => 'give_log',
			'post_status' => 'publish',
		);

		if ( ! empty( $type ) && $this->valid_type( $type ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy' => 'give_log_type',
					'field'    => 'slug',
					'terms'    => $type,
				),
			);
		}

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		$this->bc_200_validate_params( $query_args );

		if ( ! give_has_upgrade_completed( 'give_v20_logs_upgrades' ) ) {
			// Backward compatibility.
			$logs = get_posts( $query_args );

			if ( $logs ) {
				foreach ( $logs as $log ) {
					wp_delete_post( $log, true );
				}
			}
		} else {
			$logs = $this->log_db->get_logs( $query_args );

			if ( $logs ) {
				foreach ( $logs as $log ) {
					$this->log_db->delete( $log->ID );
				}
			}
		}
	}

	/**
	 * Setup cron to delete log cache in background.
	 *
	 * @since  1.7
	 * @access public
	 *
	 * @param int $post_id
	 */
	public function background_process_delete_cache( $post_id ) {
		// Delete log cache immediately
		wp_schedule_single_event( time() - 5, 'give_delete_log_cache' );
	}

	/**
	 * Delete all logging cache when form, log or payment updates
	 *
	 * @since  1.7
	 * @access public
	 *
	 * @return bool
	 */
	public function delete_cache() {
		global $wpdb;

		// Add log related keys to delete.
		$cache_option_names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT *
						FROM {$wpdb->options}
						where option_name LIKE '%%%s%%'
						OR option_name LIKE '%%%s%%'",
				'give_cache_give_logs',
				'give_cache_get_log_count'
			),
			1 // option_name
		);
		
		// Bailout.
		if ( empty( $cache_option_names ) ) {
			return false;
		}

		Give_Cache::delete( $cache_option_names );
	}

	/**
	 * Validate query params.
	 *
	 * @since  2.0
	 * @access private
	 *
	 * @param array $log_query
	 * @param array $log_meta
	 */
	private function bc_200_validate_params( &$log_query, &$log_meta = array() ) {
		$query_params = array(
			'log_title'   => 'post_title',
			'log_parent'  => 'post_parent',
			'log_content' => 'post_content',
			'log_type'    => 'tax_query',
		);

		if ( ! give_has_upgrade_completed( 'give_v20_logs_upgrades' ) ) {
			// Set old params.
			foreach ( $query_params as $new_query_param => $old_query_param ) {

				if ( isset( $log_query[ $old_query_param ] ) ) {
					if ( empty( $log_query[ $new_query_param ] ) ) {
						$log_query[ $new_query_param ] = $log_query[ $old_query_param ];
					}
					continue;
				} elseif ( ! isset( $log_query[ $new_query_param ] ) ) {
					continue;
				}

				switch ( $new_query_param ) {
					case 'log_type':
						if ( ! empty( $log_query[ $new_query_param ] ) && $this->valid_type( $log_query[ $new_query_param ] ) ) {
							$log_query['tax_query'] = array(
								array(
									'taxonomy' => 'give_log_type',
									'field'    => 'slug',
									'terms'    => $log_query[ $new_query_param ],
								),
							);
						}
						break;

					default:
						$log_query[ $old_query_param ] = $log_query[ $new_query_param ];
				}
			}
		} else {
			// Set only old params.
			$query_params = array_flip( $query_params );
			foreach ( $query_params as $old_query_param => $new_query_param ) {
				if ( isset( $log_query[ $new_query_param ] ) ) {
					if ( empty( $log_query[ $old_query_param ] ) ) {
						$log_query[ $old_query_param ] = $log_query[ $new_query_param ];
					}
					continue;
				} elseif ( ! isset( $log_query[ $old_query_param ] ) ) {
					continue;
				}

				switch ( $old_query_param ) {
					case 'tax_query':
						if ( ! empty( $log_query[ $old_query_param ] ) && isset( $log_query[ $old_query_param ][0]['terms'] ) ) {
							$log_query[ $new_query_param ] = $log_query[ $old_query_param ][0]['terms'];
						}
						break;

					default:
						if ( isset( $log_query[ $old_query_param ] ) ) {
							$log_query[ $new_query_param ] = $log_query[ $old_query_param ];
						}
				}
			}

			if ( isset( $log_query['posts_per_page'] ) ) {
				$log_query['number'] = $log_query['posts_per_page'];
			}
		}
	}

	/**
	 * Set new log properties.
	 *
	 * @since  2.0
	 * @access private
	 *
	 * @param  array $logs
	 */
	private function bc_200_add_new_properties( &$logs ) {
		if ( empty( $logs ) ) {
			return;
		}

		$query_params = array(
			'log_title'    => 'post_title',
			'log_parent'   => 'post_parent',
			'log_content'  => 'post_content',
			'log_date'     => 'post_date',
			'log_date_gmt' => 'post_date_gmt',
			'log_type'     => 'give_log_type',
		);

		if ( ! give_has_upgrade_completed( 'give_v20_logs_upgrades' ) ) {
			foreach ( $logs as $index => $log ) {
				foreach ( $query_params as $new_query_param => $old_query_param ) {
					if ( ! property_exists( $log, $old_query_param ) ) {
						/**
						 *  Set unmatched properties.
						 */

						// 1. log_type
						$term = get_the_terms( $log->ID, 'give_log_type' );
						$term = ! is_wp_error( $term ) && ! empty( $term ) ? $term[0] : array();

						$logs[ $index ]->{$new_query_param} = ! empty( $term ) ? $term->slug : '';

						continue;
					}

					switch ( $old_query_param ) {
						case 'post_parent':
							$logs[ $index ]->{$new_query_param} = give_get_meta( $log->ID, '_give_log_payment_id', true );
							break;

						default:
							$logs[ $index ]->{$new_query_param} = $log->{$old_query_param};
					}
				}
			}
		}
	}
}
