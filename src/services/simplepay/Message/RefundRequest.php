<?php

namespace webmenedzser\craftsimplepay\services\simplepay\Message;

use webmenedzser\craftsimplepay\services\simplepay\Message\AbstractRequest;
use webmenedzser\craftsimplepay\services\simplepay\Message\RefundResponse;
use webmenedzser\craftsimplepay\services\simplepay\Sdk\SimplePayRefund;

class RefundRequest extends AbstractRequest
{
    /**
     * Get API Endpoint
     *
     * @return string
     */
    public function getEndpoint()
    {
        return parent::getEndpoint() . 'v2/refund';
    }

    /**
     * @inheritDoc
     */
    public function getData()
    {
        return [
            'salt' => $this->getSalt(),
            'merchantKey' => $this->getSecretKey(),
            'merchant' => $this->getMerchant(),
            'orderRef' => $this->getTransactionReference(),
            'currency' => $this->getCurrency(),
            'language' => $this->getLanguage(),
            'SANDBOX' => $this->getTestMode()
        ];
    }

    /**
     * @inheritDoc
     */
    public function sendData($data)
    {
        $simplePayRefund = new SimplePayRefund($this->getData());
        $result = $simplePayRefund->runRefund();

        return $this->response = new RefundResponse($this, $result);
    }
}
