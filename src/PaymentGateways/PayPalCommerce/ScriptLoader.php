<?php

namespace Give\PaymentGateways\PayPalCommerce;

use Give\PaymentGateways\PayPalCommerce\Models\MerchantDetail;
use Give\PaymentGateways\PayPalCommerce\Repositories\MerchantDetails;
use Give_Admin_Settings;
use PayPalCheckoutSdk\Core\AccessTokenRequest;

/**
 * Class ScriptLoader
 * @package Give\PaymentGateways\PayPalCommerce
 *
 * @since 2.8.0
 */
class ScriptLoader {
	/**
	 * Paypal SDK handle.
	 *
	 * @since 2.8.0
	 *
	 * @var string
	 */
	private $paypalSdkScriptHandle = 'give-paypal-sdk-js';

	/**
	 * Load admin scripts
	 *
	 * @since 2.8.0
	 */
	public function loadAdminScripts() {
		if ( ! Utils::isConnected() || ! Give_Admin_Settings::is_setting_page( 'gateway', 'paypal' ) ) {
			return;
		}

		wp_enqueue_script(
			'give-paypal-partner-js',
			$this->getPartnerJsUrl(),
			[],
			null,
			true
		);

		wp_localize_script(
			'give-paypal-partner-js',
			'givePayPalCommerce',
			[
				'translations' => [
					'confirmPaypalAccountDisconnection' => esc_html__( 'Confirm PayPal account disconnection', 'give' ),
					'disconnectPayPalAccount'           => esc_html__( 'Do you want to disconnect PayPal account?', 'give' ),
				],
			]
		);

		$script = <<<EOT
				function givePayPalOnBoardedCallback(authCode, sharedId) {
					const query = '&authCode=' + authCode + '&sharedId=' + sharedId;
					fetch( ajaxurl + '?action=give_paypal_commerce_user_on_boarded' + query )
						.then(function(res){ return res.json() })
						.then(function(res) {
							if ( true !== res.success ) {
								alert("Something went wrong!");
								}
							}
						);
				}
EOT;

		wp_add_inline_script(
			'give-paypal-partner-js',
			$script
		);
	}

	/**
	 * Load public assets.
	 *
	 * @since 2.8.0
	 */
	public function loadPublicAssets() {
		if ( ! Utils::isConnected() ) {
			return;
		}

		/* @var MerchantDetail $merchant */
		$merchant = give( MerchantDetail::class );

		/**
		 * Filter PayPal sdk js query parameters.
		 *
		 * List of query parameters: https://developer.paypal.com/docs/checkout/reference/customize-sdk/#query-parameters
		 *
		 * @since 2.8.0
		 */
		$payPalSdkQueryParameters = apply_filters(
			'give_paypal_commerce_sdk_query_parameters',
			[
				'client-id'   => $merchant->clientId,
				'merchant-id' => $merchant->merchantIdInPayPal,
				'currency'    => give_get_currency(),
				'components'  => 'hosted-fields,buttons',
				'locale'      => get_locale(),
			]
		);

		wp_enqueue_script(
			$this->paypalSdkScriptHandle,
			add_query_arg( $payPalSdkQueryParameters, 'https://www.paypal.com/sdk/js' ),
			[ 'give' ],
			null,
			false
		);

		add_filter( 'script_loader_tag', [ $this, 'addAttributesToPayPalSdkScript' ], 10, 2 );

		wp_enqueue_script(
			'give-paypal-commerce-js',
			GIVE_PLUGIN_URL . 'assets/dist/js/paypal-commerce.js',
			[ $this->paypalSdkScriptHandle ],
			GIVE_VERSION,
			true
		);

		wp_enqueue_style(
			'give-paypal-commerce-css',
			GIVE_PLUGIN_URL . 'assets/dist/css/paypal-commerce.css',
			[ 'give-styles' ],
			GIVE_VERSION
		);

		wp_localize_script(
			'give-paypal-commerce-js',
			'givePayPalCommerce',
			[
				'paypalCardInfoErrorPrefixes'  => [
					'expirationDateField' => esc_html__( 'Card Expiration Date:', 'give' ),
					'cardNumberField'     => esc_html__( 'Card Number:', 'give' ),
					'cardCvcField'        => esc_html__( 'Card CVC:', 'give' ),
				],
				'cardFieldPlaceholders'        => [
					'cardNumber'     => esc_html__( 'Card Number', 'give' ),
					'cardCvc'        => esc_html__( 'CVC', 'give' ),
					'expirationDate' => esc_html__( 'MM/YY', 'give' ),
				],
				'defaultDonationCreationError' => esc_html__( 'An error occurred while processing your payment. Please try again.', 'give' ),

				// List of style properties support by PayPal for advanced card fields: https://developer.paypal.com/docs/business/checkout/reference/style-guide/#style-the-card-payments-fields
				'hostedCardFieldStyles'        => apply_filters( 'give_paypal_commerce_hosted_field_style', [] ),
			]
		);
	}

	/**
	 * Add attributes to PayPal sdk.
	 *
	 * @param string $tag
	 * @param string $handle
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function addAttributesToPayPalSdkScript( $tag, $handle ) {
		if ( $this->paypalSdkScriptHandle !== $handle ) {
			return $tag;
		}

		$tag = str_replace(
			'src=',
			sprintf(
				'data-partner-attribution-id="%1$s" data-client-token="%2$s" src=',
				PartnerDetails::$attributionId,
				MerchantDetails::getClientToken()
			),
			$tag
		);

		return $tag;
	}

	/**
	 * Get PayPal partner js url.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	private function getPartnerJsUrl() {
		return sprintf(
			'%1$swebapps/merchantboarding/js/lib/lightbox/partner.js',
			give( PayPalClient::class )->getHomePageUrl()
		);
	}
}
