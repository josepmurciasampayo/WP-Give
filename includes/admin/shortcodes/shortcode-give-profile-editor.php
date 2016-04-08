<?php
/**
 * The [give_profile_editor] Shortcode Generator class
 *
 * @package     Give
 * @subpackage  Admin
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.3.0
 */

defined( 'ABSPATH' ) or exit;

class Give_Shortcode_Profile_Editor extends Give_Shortcode_Generator {

	/**
	 * Class constructor
	 */
	public function __construct() {

		$this->shortcode['label'] = __( 'Profile Editor', 'give' );

		parent::__construct( 'give_profile_editor' );
	}
}

new Give_Shortcode_Profile_Editor;
