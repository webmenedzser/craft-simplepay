<?php
/**
 * SimplePay for Craft Commerce
 *
 * SimplePay payment gateway for Craft Commerce
 *
 * @link      https://www.webmenedzser.hu
 * @copyright Copyright (c) 2020 Ottó Radics
 */

namespace webmenedzser\craftsimplepay\gateways;

use webmenedzser\craftsimplepay\CraftSimplepay;
use webmenedzser\craftsimplepay\helpers\SimplePayHelper;
use webmenedzser\craftsimplepay\helpers\TemplateHelper;
use webmenedzser\craftsimplepay\services\IpnService;
use webmenedzser\craftsimplepay\services\simplepay\Gateway as OmnipayGateway;
use webmenedzser\craftsimplepay\services\simplepay\Sdk\SimplePayIpn;

use Craft;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\omnipay\base\OffsiteGateway;
use craft\commerce\models\Transaction;
use craft\commerce\Plugin as Commerce;
use craft\commerce\records\Transaction as TransactionRecord;
use craft\helpers\UrlHelper;
use craft\web\Response;

use Omnipay\Common\AbstractGateway;
use yii\base\NotSupportedException;

class Gateway extends OffsiteGateway
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public $merchant;

    /**
     * @var string
     */
    public $secretKey;

    /**
     * @var string
     */
    public $currency;

    // Public Methods
    // =========================================================================

    public function completePurchase(Transaction $transaction) : RequestResponseInterface
    {
        if (!$this->supportsCompletePurchase()) {
            throw new NotSupportedException(Craft::t('commerce', 'Completing purchase is not supported by this gateway'));
        }

        $request = $this->createRequest($transaction);
        $request['transactionReference'] = $transaction->reference;
        $completeRequest = $this->prepareCompletePurchaseRequest($request);

        return $this->performRequest($completeRequest, $transaction);
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t(
            'craft-simplepay',
            'OTP SimplePay v2.1'
        );
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app
            ->getView()
            ->renderTemplate(
                'craft-simplepay/gatewaySettings',
                [
                    'gateway' => $this
                ]
            );
    }

    /**
     * @inheritdoc
     */
    public function getPaymentTypeOptions(): array
    {
        return [
            'purchase' => Craft::t(
                'commerce',
                'Purchase (Authorize and Capture Immediately)'
            )
        ];
    }

    public function getPaymentFormHtml(array $params): ?string
    {
        $imageUrl = TemplateHelper::getHorizontalLogoUrl();
        $alt = Craft::t('craft-simplepay', "SimplePay customer's guide");
        $title = Craft::t('craft-simplepay', 'SimplePay - Online bank card payment');
        $href = Craft::t('craft-simplepay', 'http://simplepartner.hu/PaymentService/Payment_information.pdf');

        return '<a href="' . $href . '" class="simplepay-logo-wrapper" target="_blank"><img alt="' . $alt . '" title="' . $title . '" class="simplepay-logo" src="' . $imageUrl . '" /></a>';
    }

    public function supportsPartialRefund() : bool
    {
        return false;
    }

    public function supportsRefund() : bool
    {
        return true;
    }

    public function supportsWebhooks() : bool
    {
        return true;
    }

    public function processWebHook() : Response
    {
        $request = Craft::$app->request;
        $response = Craft::$app->response;

        $gatewayId = $request->getQueryParam('gateway');
        if (!$gatewayId) {
            return $response;
        }

        $gateway = Commerce::getInstance()
            ->gateways
            ->getGatewayById($gatewayId);
        if (!$gateway) {
            throw new \Exception('Gateway not found.');
        }

        /**
         * Call SimplePay SDK to process the response
         */
        $simplePayIpn = new SimplePayIpn([
            'SANDBOX' => CraftSimplePay::getInstance()->getSettings()->testMode,
            'merchant' => Craft::parseEnv($gateway->merchant),
            'merchantKey' => Craft::parseEnv($gateway->secretKey)
        ]);

        $json = json_encode($request->getBodyParams());
        if (!$simplePayIpn->isIpnSignatureCheck($json)) {
            $response->data = 'Signature is invalid.';

            return $response;
        }

        /**
         * Get IPN confirmation content.
         */
        $ipnConfirmation = $simplePayIpn->getIpnConfirmContent();

        /**
         * Set required response headers.
         */
        $response->headers->add('Accept-language', 'EN');
        $response->headers->add('Content-type', 'application/json');
        $response->headers->add('Signature', $ipnConfirmation['signature']);

        $transactionHash = $this->getTransactionHashFromWebhook();
        if (!$transactionHash) {
            $response->data = "No transactionHash passed.";

            return $response;
        }

        $transaction = Commerce::getInstance()->getTransactions()->getTransactionByHash($transactionHash);
        if (!$transaction) {
            $response->data = "No transaction exists with hash $transactionHash.";

            return $response;
        }

        /**
         * Prevent duplicate refunds if the refund was initiated from CP.
         */
        $transactionCode = $request->getBodyParam('transactionId');
        if (!$transactionCode) {
            $response->data = "No transaction code provided for $transactionHash.";

            return $response;
        }

        $childTransaction = Commerce::getInstance()
            ->getTransactions()
            ->createTransaction(null, $transaction);

        /**
         * Do anything only if the transaction status is finished. This will be always FALSE
         * as the webhook is called when the transaction is ready ("FINISHED") in the gateway system.
         */
        $status = (string) $request->getBodyParam('status');
        if ($status !== 'FINISHED') {
            return $response;
        }

        /**
         * If the refund IPN is called, acknowledge it.
         */
        $refundStatus = (string) $request->getBodyParam('refundStatus');
        if ($refundStatus === 'FULL' || $refundStatus === 'PARTIAL') {
            $response->data = $ipnConfirmation['confirmContent'];

            return $response;
        }

        /**
         * If the to-be-child transaction code is equal to the parent transaction code
         * and their types are the same, then the IPN call is possibly a duplicate, so return without adding
         * new transaction.
         */
        if ($transaction->type === $childTransaction->type && $transaction->code == $request->getBodyParam('transactionId')) {
            $response->data = $ipnConfirmation['confirmContent'];

            return $response;
        }

        $childTransaction->type = TransactionRecord::TYPE_PURCHASE;
        $childTransaction->status = TransactionRecord::STATUS_SUCCESS;
        $childTransaction->code = $request->getBodyParam('transactionId');
        $childTransaction->reference = $request->getBodyParam('orderRef');
        $childTransaction->message = $request->getBodyParam('status');
        $childTransaction->response = json_encode($json);

        Commerce::getInstance()->getTransactions()->saveTransaction($childTransaction);

        /**
         * Set confirmContent as body of $response.
         */
        $response->data = $ipnConfirmation['confirmContent'];

        return $response;
    }

    public function getTransactionHashFromWebhook(): null|string
    {
        $orderRef = Craft::$app->getRequest()->getBodyParam('orderRef');
        $order = SimplePayHelper::getOrderByOrderRef($orderRef);

        return $order->getLastTransaction()->hash;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createGateway(): AbstractGateway
    {
        /** @var OmnipayGateway $gateway */
        $gateway = static::createOmnipayGateway($this->getGatewayClassName());
        $carts = Commerce::getInstance()->getCarts();
        $cart = $carts ? $carts->getCart() : null;

        if (!$cart) {
            throw new \Exception('The cart is empty.');
        }

        $orderRef = $cart->number . '-' . random_int(100, 999999);
        $customer = $cart->getCustomer();
        $address = $cart->getBillingAddress();

        $gateway->setMerchant(Craft::parseEnv($this->merchant));
        $gateway->setSecretKey(Craft::parseEnv($this->secretKey));
        $gateway->setTestMode(CraftSimplepay::getInstance()->getSettings()->testMode);
        $gateway->setOrderRef($orderRef);
        $gateway->setCustomerEmail($cart->email);
        if (SimplePayHelper::shouldSendInvoiceData($address)) {
            $gateway->setInvoiceData([
                'name' => $address->fullName ?: ($address->lastName . ' ' . $address->firstName),
                'country' => $address->countryIso,
                'state' => $address->stateText,
                'city' => $address->city,
                'zip' => $address->zipCode,
                'address' => $address->address1,
                'address2' => $address->address2,
                'company' => $address->businessName
            ]);
        }
        $gateway->setLanguage('HU');
        $gateway->setTotal($cart->total);
        $gateway->setUrl(UrlHelper::actionUrl(
            'craft-simplepay/back?orderRef=' . $orderRef
        ));

        return $gateway;
    }

    /**
     * @inheritdoc
     */
    protected function getGatewayClassName(): null|string
    {
        return '\\'.OmnipayGateway::class;
    }

    // Private Methods
    // =========================================================================
}
