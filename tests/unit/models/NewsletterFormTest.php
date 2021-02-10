<?php

namespace simplonprod\newslettertests\unit\models;

use simplonprod\newsletter\models\NewsletterForm;
use simplonprod\newsletter\Newsletter;
use simplonprod\newslettertests\unit\BaseUnitTest;

class NewsletterFormTest extends BaseUnitTest
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testFailedSubscribe()
    {
        $this->tester->mockMethods(
            Newsletter::$plugin,
            'adapter',
            [
                'subscribe' => false
            ]
        );

        $newsletterForm = new NewsletterForm([
            'consent' => true,
            'email'   => 'some@email.com'
        ]);
        $this->assertFalse($newsletterForm->subscribe());
        $this->assertEquals('Some error', $newsletterForm->getFirstError('email'));
    }

    protected function _before()
    {
    }

    // tests

    protected function _after()
    {
    }
}
