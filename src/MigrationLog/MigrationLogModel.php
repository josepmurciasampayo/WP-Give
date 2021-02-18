<?php

namespace Give\MigrationLog;

/**
 * Class MigrationLogModel
 * @package Give\MigrationLog
 *
 * @since 2.10.0
 */
class MigrationLogModel {
	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var string
	 */
	private $status;

	/**
	 * @var string|null
	 */
	private $last_run;

	/**
	 * @var mixed|null
	 */
	private $error;

	/**
	 * @var int
	 */
	private $run_order;

	/**
	 * MigrationModel constructor.
	 *
	 * @param  string  $id
	 * @param  string  $status
	 * @param  mixed|null  $error
	 * @param  string|null  $lastRun
	 */
	public function __construct( $id, $status = '', $error = null, $lastRun = null ) {
		$this->id       = $id;
		$this->last_run = $lastRun;
		$this->setError( $error );
		$this->setStatus( $status );
	}

	/**
	 * Set migration status
	 *
	 * @param string $status
	 * @uses MigrationLogStatus
	 * @see MigrationLogStatus::STATUS_NAME
	 *
	 * @return MigrationLogModel
	 */
	public function setStatus( $status ) {
		$this->status = array_key_exists( $status, MigrationLogStatus::getAll() )
			? $status
			: MigrationLogStatus::getDefault();

		return $this;
	}

	/**
	 * Add migration error notice
	 *
	 * @param  mixed  $error
	 *
	 * @return MigrationLogModel
	 */
	public function setError( $error ) {
		if ( is_array( $error ) || is_object( $error ) ) {
			$error = print_r( $error, true );
		}

		$this->error = $error;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @return string
	 */
	public function getLastRunDate() {
		return $this->last_run;
	}

	/**
	 * @return string|null
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Save migration
	 */
	public function save() {
		give( MigrationLogRepository::class )->save( $this );
	}
}
