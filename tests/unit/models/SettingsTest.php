<?php

namespace juban\newslettertests\unit\models;

use juban\newsletter\adapters\Dummy;
use juban\newsletter\models\Settings;
use juban\newslettertests\unit\BaseUnitTest;

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
