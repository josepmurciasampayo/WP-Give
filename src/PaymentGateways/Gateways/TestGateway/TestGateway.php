<?php

namespace Give\PaymentGateways\Gateways\TestGateway;

use Give\Framework\PaymentGateways\Contracts\PaymentGateway;
use Give\Helpers\Form\Utils as FormUtils;
use Give\PaymentGateways\Gateways\TestGateway\Actions\PublishPaymentAndSendToSuccessPage;
use Give\PaymentGateways\Gateways\TestGateway\Views\LegacyFormFieldMarkup;

/**
 * Class TestGateway
 * @unreleased
 */
class TestGateway extends PaymentGateway {

	/**
	 * @inheritDoc
	 */
	public static function id() {
		return 'test-gateway';
	}

	/**
	 * @inheritDoc
	 */
	public function getId() {
		return self::id();
	}

	/**
	 * @inheritDoc
	 */
	public function getName() {
		return __( 'Test Gateway', 'give' );
	}

	/**
	 * @inheritDoc
	 */
	public function getPaymentMethodLabel() {
		return __( 'Test Gateway', 'give' );
	}

	/**
	 * @inheritDoc
	 */
	public function getLegacyFormFieldMarkup( $formId ) {
		if ( FormUtils::isLegacyForm( $formId ) ) {
			return false;
		}

		/** @var LegacyFormFieldMarkup $legacyFormFieldMarkup */
		$legacyFormFieldMarkup = give( LegacyFormFieldMarkup::class );

		return $legacyFormFieldMarkup();
	}

	/**
	 * @inheritDoc
	 */
	public function handleGatewayRequest( $donationId, $formData ) {
		/** @var PublishPaymentAndSendToSuccessPage $action */
		$action = give( PublishPaymentAndSendToSuccessPage::class );

		return $action( $donationId );
	}
}