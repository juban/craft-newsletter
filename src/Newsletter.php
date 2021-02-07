<?php

namespace simplonprod\newsletter;

use Craft;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Component;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use simplonprod\newsletter\adapters\BaseNewsletterAdapter;
use simplonprod\newsletter\adapters\Mailjet;
use simplonprod\newsletter\adapters\NewsletterAdapterInterface;
use simplonprod\newsletter\adapters\Sendinblue;
use simplonprod\newsletter\models\Settings;
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
 * @author    Simplon.Prod
 * @package   Newsletter
 * @since     1.0.0
 *
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class Newsletter extends Plugin
{

    const EVENT_REGISTER_NEWSLETTER_ADAPTER_TYPES = 'registerNewsletterAdapterTypes';

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
    public $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public $hasCpSettings = true;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public $hasCpSection = false;

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

        // Register adapter component
        $this->set('adapter', function () {
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

    private function _registerAfterInstallEvent(): void
    {
        // Do something after we're installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    // Create and save default settings
                    $projectConfigSettings = Craft::$app->getProjectConfig()->get('plugins.newsletter', true);
                    if (!isset($projectConfigSettings['settings'])) {
                        $adapterTypes = self::getAdaptersTypes();
                        $this->settings->adapterType = $adapterTypes[0];
                        Craft::$app->plugins->savePluginSettings(self::$plugin, $this->settings->getAttributes());
                    }

                    if (Craft::$app->getRequest()->getIsCpRequest()) {
                        // Redirect to settings page
                        Craft::$app->getResponse()->redirect(
                            UrlHelper::cpUrl('settings/plugins/newsletter')
                        )->send();
                    }
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
            Sendinblue::class
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $adaptersTypes
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_NEWSLETTER_ADAPTER_TYPES, $event);

        return $event->types;
    }

    /**
     * @param string $type
     * @param array|null $settings
     * @return NewsletterAdapterInterface
     * @throws \craft\errors\MissingComponentException
     * @throws \yii\base\InvalidConfigException
     */
    public static function createAdapter(string $type, array $settings = null): NewsletterAdapterInterface
    {
        /** @var BaseNewsletterAdapter $adapter */
        $adapter = Component::createComponent([
            'type'     => $type,
            'settings' => $settings,
        ], NewsletterAdapterInterface::class);

        return $adapter;
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
                } else {
                    $this->settings->adapterTypeSettings = $adapter->getAttributes();
                }
            } else {
                return false;
            }
        }
        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return Settings|null
     */
    protected function createSettingsModel(): ?Settings
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    protected function settingsHtml(): string
    {
        $allAdapterTypes = self::getAdaptersTypes();
        $allAdapters = [];
        $adapterTypeOptions = [];
        $adapter = $this->adapter;

        if (Craft::$app->request->post('settings')) {
            $postSettings = Craft::$app->request->post('settings');
            $adapterSettings = $postSettings['adapterSettings'][$postSettings['adapterType']] ?? [];
            $adapter = self::createAdapter($postSettings['adapterType'], $adapterSettings);
            $adapter->validate();
        }

        foreach ($allAdapterTypes as $adapterType) {
            /** @var string|NewsletterAdapterInterface $adapterType */
            $allAdapters[] = self::createAdapter($adapterType);
            $adapterTypeOptions[] = [
                'value' => $adapterType,
                'label' => $adapterType::displayName()
            ];
        }

        // Sort them by name
        ArrayHelper::multisort($adapterTypeOptions, 'label');

        return Craft::$app->view->renderTemplate(
            'newsletter/settings',
            [
                'settings'           => $this->getSettings(),
                'allAdapters'        => $allAdapters,
                'adapterTypeOptions' => $adapterTypeOptions,
                'adapter'            => $adapter
            ]
        );
    }
}
