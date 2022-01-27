<?php

namespace Give\Framework\PaymentGateways\Actions;

use Give\Framework\PaymentGateways\DataTransferObjects\GatewayRouteData;
use InvalidArgumentException;

class GenerateGatewayRouteUrl
{
    /**
     * @since 2.18.0
     * @unreleased Return escaped URL with nonce action added
     *
     * @param string $gatewayId
     * @param string $gatewayMethod
     * @param int $donationId
     * @param array|null $args
     *
     * @return string Escaped URL with nonce action added.
     */
    public function __invoke($gatewayId, $gatewayMethod, $donationId, $args = null)
    {
        $queryArgs = [
            'give-listener' => 'give-gateway',
            'give-gateway-id' => $gatewayId,
            'give-gateway-method' => $gatewayMethod,
            'give-donation-id' => $donationId,
        ];

        if ($args) {
            $queryArgs = array_merge($queryArgs, $args);
        }

        return wp_nonce_url(
            add_query_arg(
                $queryArgs,
                home_url()
            ),
            $this->getNonceActionName($queryArgs)
        );
    }

    /**
     * Return nonce action name.
     *
     * @unreleased
     *
     * @param array|GatewayRouteData $data
     *
     * @return string
     */
    public function getNonceActionName($data)
    {
        if (is_array($data)) {
            return "{{$data['give-gateway-id']}}-{$data['give-gateway-method']}-{$data['give-donation-id']}";
        }

        if ($data instanceof GatewayRouteData) {
            return "{{$data->gatewayId}}-{$data->gatewayMethod}-{$data->donationId}";
        }

        throw new InvalidArgumentException(
            'This function only accept data in array format or GatewayRouteData class object.'
        );
    }
}
