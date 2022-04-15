<?php

use Give\Framework\PaymentGateways\Contracts\SubscriptionModuleInterface;
use Give\Framework\PaymentGateways\PaymentGateway;
use Give\Framework\PaymentGateways\PaymentGatewayRegister;
use Give\PaymentGateways\DataTransferObjects\GatewayPaymentData;
use Give\PaymentGateways\DataTransferObjects\GatewaySubscriptionData;
use PHPUnit\Framework\TestCase;

/**
 * @unknown
 */
class GatewayRouteTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->registerPaymentGateway = new PaymentGatewayRegister();
        $this->registerPaymentGateway->registerGateway(GatewayRouteTestGateway::class);
    }

    public function testDeveloperShouldAbleToRegisterRouteCallback()
    {
        $gateway = give(GatewayRouteTestGateway::class);
        $callbackData = [GatewayRouteTestGatewaySubscriptionModule::class, 'handleStripeCreditCardSCA'];

        $gateway->register3rdPartyRouteMethod('handleStripeCreditCardSCA', $callbackData);

        $arrayDiff = array_diff($gateway->routeMethods['handleStripeCreditCardSCA'], $callbackData);
        $this->assertCount(0, $arrayDiff);
    }

    public function testDeveloperShouldAbleToRegisterSecureRouteCallback()
    {
        $gateway = give(GatewayRouteTestGateway::class);
        $callbackData = [GatewayRouteTestGatewaySubscriptionModule::class, 'handleStripeCreditCardSCA'];

        $gateway->deRegister3rdPartyRouteMethod('handleStripeCreditCardSCA');
        $gateway->register3rdPartyRouteMethod('handleStripeCreditCardSCA', $callbackData, true);

        $arrayDiff = array_diff($gateway->secureRouteMethods['handleStripeCreditCardSCA'], $callbackData);
        $this->assertCount(0, $arrayDiff);
    }
}

class GatewayRouteTestGateway extends PaymentGateway
{
    public function getLegacyFormFieldMarkup($formId, $args)
    {
        return '';
    }

    public static function id()
    {
        return 'GatewayRouteTestGateway';
    }

    public function getId()
    {
        return self::id();
    }

    public function getName()
    {
        return self::id();
    }

    public function getPaymentMethodLabel()
    {
        return self::id();
    }

    public function createPayment(GatewayPaymentData $paymentData)
    {
    }
}

class GatewayRouteTestGatewaySubscriptionModule implements SubscriptionModuleInterface
{
    public function createSubscription(
        GatewayPaymentData $paymentData,
        GatewaySubscriptionData $subscriptionData
    ) {
    }

    public function handleStripeCreditCardSCA()
    {
        return __FUNCTION__;
    }
}
