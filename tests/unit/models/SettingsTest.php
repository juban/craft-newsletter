<?php

namespace simplonprod\newslettertests\unit\models;

use simplonprod\newsletter\adapters\Dummy;
use simplonprod\newsletter\models\Settings;
use simplonprod\newslettertests\unit\BaseUnitTest;

class SettingsTest extends BaseUnitTest
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testSuccessfulValidation()
    {
        $settings = new Settings();
        $settings->adapterType = Dummy::class;
        $settings->adapterTypeSettings = [
            'someAttribute' => true
        ];
        $this->assertTrue($settings->validate());
    }

    public function testFailedValidation()
    {
        $settings = new Settings();
        $this->assertFalse($settings->validate());
    }

    protected function _before()
    {
    }

    // tests

    protected function _after()
    {
    }
}
