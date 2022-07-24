<?php

namespace juban\newslettertests\unit\models;

use juban\newsletter\models\NewsletterForm;
use juban\newsletter\Newsletter;
use juban\newslettertests\unit\BaseUnitTest;

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
