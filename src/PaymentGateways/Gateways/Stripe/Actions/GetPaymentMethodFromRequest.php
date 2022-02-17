<?php

namespace Give\PaymentGateways\Gateways\Stripe\Actions;

use Give\PaymentGateways\DataTransferObjects\GatewayPaymentData;
use Give\PaymentGateways\Gateways\Stripe\Exceptions\PaymentMethodException;
use Give\PaymentGateways\Gateways\Stripe\ValueObjects\PaymentMethod;
use Give\PaymentGateways\Gateways\Stripe\WorkflowAction;

class GetPaymentMethodFromRequest extends WorkflowAction
{
    /**
     * @unreleased
     * @param GatewayPaymentData $paymentData
     * @throws PaymentMethodException
     */
    public function __invoke( GatewayPaymentData $paymentData )
    {
        if( ! isset( $_POST['give_stripe_payment_method'] ) ) {
            throw new PaymentMethodException('Payment Method Not Found');
        }

        $paymentMethod = new PaymentMethod(
            give_clean( $_POST['give_stripe_payment_method'] )
        );

        give_update_meta($paymentData->donationId, '_give_stripe_source_id', $paymentMethod->id());
        give_insert_payment_note($paymentData->donationId, sprintf(__('Stripe Source/Payment Method ID: %s', 'give'), $paymentMethod->id()));

        $this->bind( $paymentMethod );
    }
}
