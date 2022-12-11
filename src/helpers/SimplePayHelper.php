<?php
/**
 * SimplePay for Craft Commerce
 *
 * SimplePay payment gateway for Craft Commerce
 *
 * @link      https://www.webmenedzser.hu
 * @copyright Copyright (c) 2020 Ottó Radics
 */

namespace webmenedzser\craftsimplepay\helpers;

use craft\base\Component;
use craft\commerce\elements\Order;
use craft\commerce\models\Address;
use craft\commerce\Plugin as Commerce;

/**
 * Class SimplePayHelper
 *
 * @package webmenedzser\craftsimplepay\helpers
 */
class SimplePayHelper extends Component
{
    /**
     * Get Order by orderRef string
     *
     * @param string $orderRef
     *
     * @return Order
     * @throws \Exception
     */
    public static function getOrderByOrderRef(string $orderRef) : Order
    {
        /**
         * Random string is appended to order nr. before redirecting to offsite
         * payment gateway. Order nr. in Craft Commerce consists of 32 characters.
         * We should trim the characters to the first 32.
         */
        $orderNumber = substr($orderRef, 0, 32);

        /**
         * Get order by number
         *
         * @var Order $order
         */
        $order = Commerce::getInstance()
            ->getOrders()
            ->getOrderByNumber($orderNumber);

        if (!$order) {
            throw new \Exception('Order not found.');
        }

        return $order;
    }

    /**
     * Check for billing address name.
     *
     * @param Address $address
     *
     * @return bool
     */
    public static function shouldSendInvoiceData(Address $address) : bool
    {
        /**
         * If fullName is missing, check for firstName and lastName separately.
         * If both firstName and lastName are missing, fail.
         *
         * Prevents error 5309.
         */
        if (!$address->fullName && !($address->firstName || $address->lastName)) {
            return false;
        }

        /**
         * SimplePay docs missing an error code for this, payment is processed, but
         * the documentation DOES NOT state explicitly the country ISO is optional.
         * Just to be on the safe side and to prevent a possible future breaking
         * API change, e.g.: SimplePay decides country is required without notifying us
         * first, prevent sending invoice data.
         */
        if (!$address->countryIso) {
            return false;
        }

        /**
         * Prevents error 5310.
         */
        if (!$address->city) {
            return false;
        }

        /**
         * Prevents error 5311.
         */
        if (!$address->zipCode) {
            return false;
        }

        /**
         * Prevents error 5312.
         */
        if (!$address->address1) {
            return false;
        }

        return true;
    }
}
