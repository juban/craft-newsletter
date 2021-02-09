<?php
/**
 * Newsletter plugin for Craft CMS 3.x
 *
 * Craft CMS Newsletter plugin
 *
 * @link      https://www.simplonprod.co
 * @copyright Copyright (c) 2021 Simplon.Prod
 */

namespace simplonprod\newslettertests\unit;

use Craft;
use simplonprod\newsletter\adapters\BaseNewsletterAdapter;
use simplonprod\newsletter\adapters\Dummy;
use simplonprod\newsletter\Newsletter;
use UnitTester;

/**
 * ExampleUnitTest
 *
 *
 * @author    Simplon.Prod
 * @package   Newsletter
 * @since     1.0.0
 */
class PluginUnitTest extends BaseUnitTest
{
    // Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    // Public methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     *
     */
    public function testPluginInstance()
    {
        $this->assertInstanceOf(
            Newsletter::class,
            Newsletter::$plugin
        );
    }

    /**
     *
     */
    public function testPluginInstallation()
    {
        $this->assertNull(Craft::$app->getPlugins()->getPlugin('newsletter')->uninstall());
        $this->assertNull(Craft::$app->getPlugins()->getPlugin('newsletter')->install());
    }

    /**
     *
     */
    public function testCraftEdition()
    {
        Craft::$app->setEdition(Craft::Pro);

        $this->assertSame(
            Craft::Pro,
            Craft::$app->getEdition()
        );
    }

    public function testGetAdaptersTypes()
    {
        $this->tester->expectEvent(Newsletter::class, Newsletter::EVENT_REGISTER_NEWSLETTER_ADAPTER_TYPES, function () {
            Newsletter::getAdaptersTypes();
        });
        $adapters = Newsletter::getAdaptersTypes();
        $this->assertIsArray($adapters);
        foreach ($adapters as $adapter) {
            $this->assertSame(BaseNewsletterAdapter::class, get_parent_class($adapter));
        }
    }

    public function testSuccessfulBeforeSaveSettings()
    {
        // Force post request
        $_POST[Craft::$app->request->methodParam] = 'post';

        // Disable CSRF validation
        Craft::$app->request->enableCsrfValidation = false;

        \Craft::$app->request->setBodyParams([
            'pluginHandle' => 'newsletter',
            'settings'     => [
                'adapterType'     => Dummy::class,
                'adapterSettings' => [
                    Dummy::class => [
                        'someAttribute' => 'azertyuiop'
                    ]
                ]
            ]
        ]);

        $this->assertTrue(Newsletter::$plugin->beforeSaveSettings());
    }

    public function testFailedBeforeSaveSettings()
    {
        // Force post request
        $_POST[Craft::$app->request->methodParam] = 'post';

        // Disable CSRF validation
        Craft::$app->request->enableCsrfValidation = false;

        \Craft::$app->request->setBodyParams([
            'pluginHandle' => 'newsletter',
            'settings'     => []
        ]);

        $this->assertFalse(Newsletter::$plugin->beforeSaveSettings());

        \Craft::$app->request->setBodyParams([
            'pluginHandle' => 'newsletter',
            'settings'     => [
                'adapterType'     => Dummy::class,
                'adapterSettings' => []
            ]
        ]);

        $this->assertFalse(Newsletter::$plugin->beforeSaveSettings());
    }
}
