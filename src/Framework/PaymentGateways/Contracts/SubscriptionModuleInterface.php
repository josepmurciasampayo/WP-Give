<?php

namespace Give\Framework\PaymentGateways\Contracts;

use Give\Donations\Models\Donation;
use Give\Framework\PaymentGateways\Commands\GatewayCommand;
use Give\PaymentGateways\DataTransferObjects\GatewayPaymentData;
use Give\PaymentGateways\DataTransferObjects\GatewaySubscriptionData;
use Give\Subscriptions\Models\Subscription;

interface SubscriptionModuleInterface
{
    /**
     * Create a subscription with gateway
     *
     * @since 2.18.0
     *
     * @param GatewayPaymentData $paymentData
     * @param GatewaySubscriptionData $subscriptionData
     *
     * @return GatewayCommand
     */
    public function createSubscription(GatewayPaymentData $paymentData, GatewaySubscriptionData $subscriptionData);

    /**
     * Cancel subscription.
     *
     * @unreleased
     *
     * @param Subscription $subscriptionModel
     *
     * @return void
     */
    public function cancelSubscription(Subscription $subscriptionModel);

    /**
     * refund subscription payment.
     *
     * @unreleased
     *
     * @param Donation $donationModel
     * @param Subscription $subscriptionModel
     *
     * @return void
     */
    public function refundSubscriptionPayment(Donation $donationModel, Subscription $subscriptionModel );

    /**
     * Return flag whether donor can edit subscription.
     *
     * @unreleased
     *
     * @param Subscription $subscriptionModel
     *
     * @return bool
     */
    public function canEditPaymentGatewaySubscription(Subscription $subscriptionModel);

    /**
     * Return flag whether subscription synchronizable.
     *
     * @unreleased
     *
     * @param Subscription $subscriptionModel
     *
     * @return bool
     */
    public function canSyncSubscriptionWithPaymentGateway(Subscription $subscriptionModel);

    /**
     * Update subscription amount.
     *
     * @unreleased
     *
     * @param Subscription $subscriptionModel
     *
     * @return bool
     */
    public function canUpdatePaymentGatewaySubscriptionAmount(Subscription $subscriptionModel);

    /**
     * Update subscription amount.
     *
     * @unreleased
     *
     * @param Subscription $subscriptionModel
     *
     * @return bool
     */
    public function canUpdatePaymentGatewaySubscriptionPaymentMethod(Subscription $subscriptionModel);

    /**
     * Return gateway subscription detail page url.
     *
     * @unreleased
     *
     * @param string $gatewaySubscriptionId
     * @param string $donationMode
     *
     * @return string
     */
    public function gatewaySubscriptionDetailPageUrl( $gatewaySubscriptionId, $donationMode );
}
