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

use Codeception\Test\Unit;
use Craft;
use simplonprod\newsletter\adapters\BaseNewsletterAdapter;
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
class PluginUnitTest extends Unit
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
        $this->tester->expectEvent(Newsletter::class, Newsletter::EVENT_REGISTER_NEWSLETTER_ADAPTER_TYPES, function() {
            Newsletter::getAdaptersTypes();
        });
        $adapters = Newsletter::getAdaptersTypes();
        $this->assertIsArray($adapters);
        foreach ($adapters as $adapter) {
            $this->assertSame(BaseNewsletterAdapter::class, get_parent_class($adapter));
        }
    }
}
