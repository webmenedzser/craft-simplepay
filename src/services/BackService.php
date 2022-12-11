<?php
/**
 * SimplePay for Craft Commerce
 *
 * SimplePay payment gateway for Craft Commerce
 *
 * @link      https://www.webmenedzser.hu
 * @copyright Copyright (c) 2020 Ottó Radics
 */

namespace webmenedzser\craftsimplepay\services;

use webmenedzser\craftsimplepay\helpers\SimplePayHelper;
use webmenedzser\craftsimplepay\services\simplepay\Sdk\SimplePayBack;
use webmenedzser\craftsimplepay\services\ResultService;
use webmenedzser\craftsimplepay\CraftSimplepay;

use Craft;
use craft\base\Component;
use craft\commerce\Plugin as Commerce;

/**
 * Class BackService
 *
 * @package webmenedzser\craftsimplepay\controllers
 */
class BackService extends Component
{
    public static function redirectUser($request) : string
    {
        /**
         * Get parameters from return URL
         */
        $r = $request->getParam('r');
        $s = $request->getParam('s');
        $orderRef = $request->getParam('orderRef');
        $order = SimplePayHelper::getOrderByOrderRef($orderRef);

        /**
         * Get Gateway ID from Order
         */
        $gatewayId = $order->gatewayId;
        $gateway = Commerce::getInstance()
            ->gateways
            ->getGatewayById($gatewayId);

        if (!$gateway) {
            throw new \Exception('Gateway not found.');
        }

        /**
         * Call SimplePay SDK to process the response
         */
        $simplePayBack = new SimplePayBack([
            'SANDBOX' => CraftSimplepay::getInstance()->getSettings()->testMode,
            'merchant' => Craft::parseEnv($gateway->merchant),
            'merchantKey' => Craft::parseEnv($gateway->secretKey)
        ]);
        $simplePayBack->isBackSignatureCheck($r, $s);

        if (!$simplePayBack->request['checkCtrlResult']) {
            throw new \Exception('Invalid `checkCtrlResult` in response.');
        }

        $resultService = new ResultService($simplePayBack->getRawNotification(), $order);

        return $resultService->getRedirectUrl();
    }
}
