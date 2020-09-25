<?php

namespace Give\Framework\Migrations;

use Exception;
use Give\Framework\Migrations\Contracts\Migration;
use Give_Notices;

/**
 * Class MigrationsRunner
 *
 * @since 2.9.0
 */
class MigrationsRunner {
	/**
	 * Option name to store competed migrations.
	 *
	 * @var string
	 */
	private $optionNameToStoreCompletedMigrations = 'give_database_migrations';

	/**
	 * List of completed migrations.
	 *
	 * @since 2.9.0
	 *
	 * @var array
	 */
	private $completedMigrations;

	/**
	 * @since 2.9.0
	 *
	 * @var MigrationsRegister
	 */
	private $migrationRegister;

	/**
	 *  MigrationsRunner constructor.
	 *
	 * @param MigrationsRegister $migrationRegister
	 */
	public function __construct( MigrationsRegister $migrationRegister ) {
		$this->migrationRegister = $migrationRegister;

		$this->completedMigrations = get_option( $this->optionNameToStoreCompletedMigrations, [] );
	}

	/**
	 * Run database migrations.
	 *
	 * @since 2.9.0
	 */
	public function run() {
		global $wpdb;

		if ( ! $this->hasMigrationToRun() ) {
			return;
		}

		// Store and sort migrations by timestamp
		$migrations = [];

		foreach ( $this->migrationRegister->getMigrations() as $migrationClass ) {
			/* @var Migration $migrationClass */
			$migrations[ $migrationClass::timestamp() . '_' . $migrationClass::id() ] = $migrationClass;
		}

		ksort( $migrations );

		// Process migrations.
		$newMigrations = [];

		// Begin transaction
		$wpdb->query( 'START TRANSACTION' );

		try {
			foreach ( $migrations as $migrationClass ) {
				$migrationId = $migrationClass::id();

				if ( in_array( $migrationId, $this->completedMigrations, true ) ) {
					continue;
				}

				/** @var Migration $migration */
				$migration = give( $migrationClass );

				$migration->run();

				$newMigrations[] = $migrationId;
			}
		} catch ( Exception $exception ) {
			$wpdb->query( 'ROLLBACK' );

			give_record_log( 'Migration Failed', print_r( $exception, true ), 0, 'update' );
			give()->notices->register_notice(
				[
					'id'          => 'migration-failure',
					'description' => sprintf(
						'%1$s <a href="https://givewp.com/support/">https://givewp.com/support</a>',
						esc_html__( 'There was a problem running the migrations. Please reach out to GiveWP support for assistance:', 'give' )
					),
				]
			);

			return;
		}

		// Commit transaction if successful
		$wpdb->query( 'COMMIT' );

		// Save processed migrations.
		$this->completedMigrations = array_unique( array_merge( $this->completedMigrations, $newMigrations ) );

		if ( $newMigrations ) {
			update_option(
				$this->optionNameToStoreCompletedMigrations,
				$this->completedMigrations
			);
		}
	}

	/**
	 * Return whether or not all migrations completed.
	 *
	 * @since 2.9.0
	 *
	 * @return bool
	 */
	public function hasMigrationToRun() {
		return (bool) array_diff( $this->migrationRegister->getRegisteredIds(), $this->completedMigrations );
	}
}
