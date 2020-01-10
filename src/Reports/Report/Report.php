<?php

/**
 * Abstract Report class
 *
 * @package Give
 */

namespace Give\Reports\Report;

defined('ABSPATH') || exit;

/**
 * Common functionality for reports. Override this class.
 */
abstract class Report {

    /**
     * Variables used to register block type
     *
     * @var string
     */
    protected $period = [];

    /**
     * Initialize.
     */
    public function __construct() {
        //Do nothing
    }

}
