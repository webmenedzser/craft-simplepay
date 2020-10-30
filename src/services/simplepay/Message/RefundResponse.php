<?php

namespace webmenedzser\craftsimplepay\services\simplepay\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class RefundResponse extends AbstractResponse implements ResponseInterface
{
    public function getTransactionReference()
    {
        return $this->data['orderRef'];
    }

    public function getCode()
    {
        return $this->data['refundTransactionId'];
    }

    public function getMessage()
    {
        return $this->data['transactionId'];
    }

    /**
     * @return bool
     */
    public function isSuccessful() : bool
    {
        if (isset($this->data['errorCodes']) && !empty($this->data['errorCodes'])) {
            return false;
        }

        return true;
    }
}
