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

use Craft;
use craft\base\Component;

/**
 * Class TemplateHelper
 *
 * @package webmenedzser\craftsimplepay\helpers
 */
class TemplateHelper extends Component
{
    public static function getHorizontalLogoUrl()
    {
        return Craft::$app->assetManager->getPublishedUrl('@webmenedzser/craftsimplepay/assets/icon-horizontal.png', true);
    }

    public static function getVerticalLogoUrl()
    {
        return Craft::$app->assetManager->getPublishedUrl('@webmenedzser/craftsimplepay/assets/icon-vertical.png', true);
    }

    public static function getHorizontalVectorLogoUrl()
    {
        return Craft::$app->assetManager->getPublishedUrl('@webmenedzser/craftsimplepay/assets/icon-horizontal.svg', true);
    }

    public static function getVerticalVectorLogoUrl()
    {
        return Craft::$app->assetManager->getPublishedUrl('@webmenedzser/craftsimplepay/assets/icon-vertical.svg', true);
    }
}
