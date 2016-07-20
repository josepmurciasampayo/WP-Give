<?php
/**
 * The [give_receipt] Shortcode Generator class
 *
 * @package     Give
 * @subpackage  Admin
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.3.0
 */

defined( 'ABSPATH' ) or exit;

class Give_Shortcode_Donation_Receipt extends Give_Shortcode_Generator {

	/**
	 * Class constructor
	 */
	public function __construct() {

		$this->shortcode['title'] = esc_html( 'Donation Receipt', 'give' );
		$this->shortcode['label'] = esc_html( 'Donation Receipt', 'give' );

		parent::__construct( 'give_receipt' );
	}

	/**
	 * Define the shortcode attribute fields
	 *
	 * @return array
	 */
	public function define_fields() {

		return array(
			array(
				'type' => 'container',
				'html' => sprintf( '<p class="strong">%s</p>', esc_html( 'Optional settings', 'give' ) ),
			),
			array(
				'type'    => 'listbox',
				'name'    => 'price',
				'label'   => esc_html( 'Show Donation Amount:', 'give' ),
				'options' => array(
					'true'  => esc_html( 'Show', 'give' ),
					'false' => esc_html( 'Hide', 'give' ),
				),
			),
			array(
				'type'    => 'listbox',
				'name'    => 'donor',
				'label'   => esc_html( 'Show Donor Name:', 'give' ),
				'options' => array(
					'true'  => esc_html( 'Show', 'give' ),
					'false' => esc_html( 'Hide', 'give' ),
				),
			),
			array(
				'type'    => 'listbox',
				'name'    => 'date',
				'label'   => esc_html( 'Show Date:', 'give' ),
				'options' => array(
					'true'  => esc_html( 'Show', 'give' ),
					'false' => esc_html( 'Hide', 'give' ),
				),
			),
			array(
				'type'    => 'listbox',
				'name'    => 'payment_key',
				'label'   => esc_html( 'Show Payment Key:', 'give' ),
				'options' => array(
					'true'  => esc_html( 'Show', 'give' ),
					'false' => esc_html( 'Hide', 'give' ),
				),
			),
			array(
				'type'    => 'listbox',
				'name'    => 'payment_method',
				'label'   => esc_html( 'Show Payment Method:', 'give' ),
				'options' => array(
					'true'  => esc_html( 'Show', 'give' ),
					'false' => esc_html( 'Hide', 'give' ),
				),
			),
			array(
				'type'    => 'listbox',
				'name'    => 'payment_id',
				'label'   => esc_html( 'Show Payment ID:', 'give' ),
				'options' => array(
					'true'  => esc_html( 'Show', 'give' ),
					'false' => esc_html( 'Hide', 'give' ),
				),
			),
		);
	}
}

new Give_Shortcode_Donation_Receipt;
