<?php
namespace Give\Views\Form\Templates\Sequoia;

use Give\Form\Template;
use Give\Form\Template\Hookable;
use Give\Form\Template\Scriptable;
use function Give\Helpers\Form\Template\get as getThemeOptions;

/**
 * Class Sequoia
 *
 * @package Give\Views\Form\Templates
 */
class Sequoia extends Template implements Hookable, Scriptable {
	/**
	 * Map form template settings to legacy form settings.
	 *
	 * @since 2.7.0
	 * @var array
	 */
	protected $mapToLegacySetting = [
		'payment_information' => [
			'checkout_label' => '_give_checkout_label',
		],
	];

	/**
	 * @inheritDoc
	 */
	public function loadHooks() {
		$actions = new Actions();
		$actions->init();
	}

	/**
	 * @inheritDoc
	 */
	public function loadScripts() {

		// Localize Template options
		$templateOptions = getThemeOptions();

		// Set defaults
		$templateOptions['introduction']['donate_label']          = ! empty( $templateOptions['introduction']['donate_label'] ) ? $templateOptions['introduction']['donate_label'] : __( 'Donate Now', 'give' );
		$templateOptions['introduction']['primary_color']         = ! empty( $templateOptions['introduction']['primary_color'] ) ? $templateOptions['introduction']['primary_color'] : '#28C77B';
		$templateOptions['payment_amount']['next_label']          = ! empty( $templateOptions['payment_amount']['next_label'] ) ? $templateOptions['payment_amount']['next_label'] : __( 'Continue', 'give' );
		$templateOptions['payment_amount']['header_label']        = ! empty( $templateOptions['payment_amount']['header_label'] ) ? $templateOptions['payment_amount']['header_label'] : __( 'Choose Amount', 'give' );
		$templateOptions['payment_information']['header_label']   = ! empty( $templateOptions['payment_information']['header_label'] ) ? $templateOptions['payment_information']['header_label'] : __( 'Add Your Information', 'give' );
		$templateOptions['payment_information']['checkout_label'] = ! empty( $templateOptions['payment_information']['checkout_label'] ) ? $templateOptions['payment_information']['checkout_label'] : __( 'Process Donation', 'give' );

		wp_enqueue_style( 'give-google-font-montserrat', 'https://fonts.googleapis.com/css?family=Montserrat:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&display=swap', [], GIVE_VERSION );
		wp_enqueue_style( 'give-sequoia-template-css', GIVE_PLUGIN_URL . 'assets/dist/css/give-sequoia-template.css', [ 'give-styles' ], GIVE_VERSION );

		$primaryColor = $templateOptions['introduction']['primary_color'];
		$dynamic_css  = "
			.seperator {
				background: {$primaryColor}!important;
			}
			.give-btn {
				border: 2px solid {$primaryColor}!important;
				background: {$primaryColor}!important;
			}
			.give-btn:hover {
				background: {$primaryColor}!important;
			}
			.give-donation-level-btn {
				border: 2px solid {$primaryColor}!important;
			}
			.give-donation-level-btn.give-default-level {
				color: {$primaryColor}!important; background: #fff!important;
				transition: background 0.2s ease, color 0.2s ease;
			}
			.give-donation-level-btn.give-default-level:hover {
				color: {$primaryColor}!important; background: #fff!important;
			}
			.give-input:focus, .give-select:focus {
				border: 1px solid {$primaryColor}!important;
			}
			input[type='radio'] + label::after {
				background: {$primaryColor}!important;
			}
		";
		wp_add_inline_style( 'give-sequoia-template-css', $dynamic_css );

		wp_enqueue_script( 'give-sequoia-template-js', GIVE_PLUGIN_URL . 'assets/dist/js/give-sequoia-template.js', [ 'give' ], GIVE_VERSION, true );
		wp_localize_script( 'give-sequoia-template-js', 'sequoiaTemplateOptions', $templateOptions );
	}

	/**
	 * @inheritDoc
	 */
	public function getID() {
		return 'sequoia';
	}

	/**
	 * @inheritDoc
	 */
	public function getName() {
		return __( 'Sequoia - Multi-Step Form', 'give' );
	}

	/**
	 * @inheritDoc
	 */
	public function getImage() {
		return 'https://images.unsplash.com/photo-1448387473223-5c37445527e7?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=300&q=100';
	}

	/**
	 * @inheritDoc
	 */
	public function getOptionsConfig() {
		return require 'optionConfig.php';
	}
}
