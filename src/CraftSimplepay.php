<?php
/**
 * SimplePay for Craft Commerce
 *
 * SimplePay payment gateway for Craft Commerce
 *
 * @link      https://www.webmenedzser.hu
 * @copyright Copyright (c) 2020 Ottó Radics
 */

namespace webmenedzser\craftsimplepay;

use webmenedzser\craftsimplepay\models\Settings;
use webmenedzser\craftsimplepay\gateways\Gateway;
use webmenedzser\craftsimplepay\helpers\TemplateHelper;

use Craft;
use craft\base\Plugin;
use craft\commerce\services\Gateways;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Plugins;
use craft\web\twig\variables\CraftVariable;

use yii\base\Event;

/**
 * Class CraftSimplepay
 *
 * @author    Ottó Radics
 * @package   CraftSimplepay
 * @since     1.0.0
 *
 */
class CraftSimplepay extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var CraftSimplepay
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $schemaVersion = '1.0.1';

    /**
     * @var bool
     */
    public bool $hasCpSettings = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->_registerGateway();
        $this->_registerTemplateHelper();

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
        );
    }

    // Protected Methods
    // =========================================================================
    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate(
            'craft-simplepay/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }

    // Private Methods
    // =========================================================================

    /**
     * Register payment gateway in Craft Commerce
     */
    private function _registerGateway() : void
    {
        Event::on(
            Gateways::class,
            Gateways::EVENT_REGISTER_GATEWAY_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = Gateway::class;
            }
        );
    }

    private function _registerTemplateHelper() : void
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $e) {
                /** @var CraftVariable $variable */
                $variable = $e->sender;

                // Attach a service:
                $variable->set('simplepay', TemplateHelper::class);
            }
        );
    }
}
