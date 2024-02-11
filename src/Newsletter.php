<?php

namespace juban\newsletter;

use Craft;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Component;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use juban\googlerecaptcha\GoogleRecaptcha;
use juban\newsletter\adapters\Brevo;
use juban\newsletter\adapters\Mailchimp;
use juban\newsletter\adapters\Mailjet;
use juban\newsletter\adapters\NewsletterAdapterInterface;
use juban\newsletter\models\NewsletterForm;
use juban\newsletter\models\Settings;
use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
 * @property NewsletterAdapterInterface $adapter
 *
 * @author    juban
 * @package   Newsletter
 * @since     1.0.0
 *
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class Newsletter extends Plugin
{
    public const EVENT_REGISTER_NEWSLETTER_ADAPTER_TYPES = 'registerNewsletterAdapterTypes';

    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Newsletter::$plugin
     *
     * @var Newsletter
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public string $schemaVersion = '1.1.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public bool $hasCpSettings = true;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public bool $hasCpSection = false;

    /**
     * Initializes the plugin.
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Set a @modules alias pointed to the modules/ directory
        Craft::setAlias('@newsletter', __DIR__);

        $this->_registerAfterInstallEvent();
        $this->_registerRecaptchaVerification();

        // Register adapter component
        $this->set('adapter', function() {
            $adapterTypes = self::getAdaptersTypes();
            // Backward compatibility with legacy adapters
            if (str_starts_with($this->settings->adapterType, "simplonprod")) {
                $this->settings->adapterType = str_replace('simplonprod', 'juban', $this->settings->adapterType);
            }

            if ($this->settings->adapterType === null) {
                $this->settings->adapterType = $adapterTypes[0];
                $this->settings->adapterTypeSettings = [];
            }

            return self::createAdapter($this->settings->adapterType, $this->settings->adapterTypeSettings);
        });

        Craft::info(
            Craft::t(
                'newsletter',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    /**
     * Redirect user to plugin setting after installation if from CP
     */
    private function _registerAfterInstallEvent(): void
    {
        // Do something after we're installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function(PluginEvent $event) {
                if ($event->plugin !== $this) {
                    return;
                }

                if (!Craft::$app->getRequest()->getIsCpRequest()) {
                    return;
                }

                // Redirect to settings page
                Craft::$app->getResponse()->redirect(
                    UrlHelper::cpUrl('settings/plugins/newsletter')
                )->send();
            }
        );
    }

    /**
     * Verify user submission with Google reCAPTCHA plugin if enabled
     */
    private function _registerRecaptchaVerification(): void
    {
        if (App::parseBooleanEnv(Newsletter::$plugin->settings->recaptchaEnabled) !== true) {
            return;
        }

        if (!Craft::$app->plugins->isPluginEnabled('google-recaptcha')) {
            return;
        }

        Event::on(
            NewsletterForm::class,
            NewsletterForm::EVENT_AFTER_VALIDATE,
            static function(Event $event) {
                $form = $event->sender;
                if (!GoogleRecaptcha::$plugin->recaptcha->verify()) {
                    $form->addError('recaptcha', Craft::t('newsletter', 'Please prove you are not a robot.'));
                }
            }
        );
    }

    /**
     * Return the list of available newsletter adapters
     * @return string[]
     */
    public static function getAdaptersTypes(): array
    {
        $adaptersTypes = [
            Mailjet::class,
            Brevo::class,
            Mailchimp::class,
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $adaptersTypes,
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_NEWSLETTER_ADAPTER_TYPES, $event);

        return $event->types;
    }

    // Protected Methods
    // =========================================================================
    /**
     * @param array|null $settings
     * @return \craft\base\ComponentInterface
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\InvalidConfigException
     */
    public static function createAdapter(string $type, array $settings = null): \craft\base\ComponentInterface
    {
        return Component::createComponent([
            'type' => $type,
            'settings' => $settings,
        ], NewsletterAdapterInterface::class);
    }

    public function beforeSaveSettings(): bool
    {
        if (Craft::$app->request->isPost) {
            $postSettings = Craft::$app->request->post('settings');
            if (isset($postSettings['adapterType'])) {
                $adapterSettings = $postSettings['adapterSettings'][$postSettings['adapterType']] ?? [];
                $adapter = self::createAdapter($postSettings['adapterType'], $adapterSettings);
                if (!$adapter->validate()) {
                    return false;
                }

                $this->settings->adapterTypeSettings = $adapter->getAttributes();
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return Settings|null
     */
    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): ?string
    {
        $allAdapterTypes = self::getAdaptersTypes();
        $allAdapters = [];
        $adapterTypeOptions = [];
        $adapter = null;

        if (Craft::$app->request->post('settings')) {
            $postSettings = Craft::$app->request->post('settings');
            $adapterSettings = $postSettings['adapterSettings'][$postSettings['adapterType']] ?? [];
            $adapter = self::createAdapter($postSettings['adapterType'], $adapterSettings);
            $adapter->validate();
        }

        if (!$adapter instanceof \craft\base\ComponentInterface) {
            $adapter = $this->adapter;
        }

        // Create every available adapter
        foreach ($allAdapterTypes as $adapterType) {
            /** @var string|NewsletterAdapterInterface $adapterType */
            $allAdapters[] = self::createAdapter($adapterType);
            $adapterTypeOptions[] = [
                'value' => $adapterType,
                'label' => $adapterType::displayName(),
            ];
        }

        // Sort them by name
        ArrayHelper::multisort($adapterTypeOptions, 'label');

        // check if a configuration file may override Control Panel settings
        $configService = Craft::$app->getConfig();
        $config = $configService->getConfigFromFile('newsletter');
        if (!empty($config)) {
            $configPath = $configService->getConfigFilePath('newsletter');
        }

        return Craft::$app->view->renderTemplate(
            'newsletter/settings',
            [
                'settings' => $this->getSettings(),
                'allAdapters' => $allAdapters,
                'adapterTypeOptions' => $adapterTypeOptions,
                'adapter' => $adapter,
                'configPath' => $configPath ?? null,
            ]
        );
    }
}
