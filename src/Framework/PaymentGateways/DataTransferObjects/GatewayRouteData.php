<?php

namespace Give\Framework\PaymentGateways\DataTransferObjects;

/**
 * Class GatewayRouteData
 * @since 2.18.0
 */
class GatewayRouteData
{
    /**
     * @var string
     */
    public $gatewayId;
    /**
     * @var string
     */
    public $gatewayMethod;
    /**
     * @var array
     */
    public $queryParams;
    /**
     * @var string|null
     */
    public $routeSignature;
    /**
     * @var string|null
     */
    public $routeSignatureId;

    /**
     * Convert data from request into DTO
     *
     * @since 2.18.0
     *
     * @return self
     */
    public static function fromRequest(array $request)
    {
        $self = new static();

        $self->gatewayId = $request['give-gateway-id'];
        $self->gatewayMethod = $request['give-gateway-method'];
        $self->routeSignature = isset($request['give-route-signature']) ? $request['give-route-signature'] : null;
        $self->routeSignatureId = isset($request['give-route-signature-id']) ? $request['give-route-signature-id'] : null;

        $self->queryParams = array_filter($request, static function ($param) {
            return !in_array($param, ['give-listener', 'give-gateway-id', 'give-gateway-method', 'give-route-signature']
            );
        }, ARRAY_FILTER_USE_KEY);

        return $self;
    }
}
