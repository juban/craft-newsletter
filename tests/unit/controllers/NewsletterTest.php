<?php

namespace juban\newslettertests\unit\controllers;

use Codeception\Stub;
use Codeception\Stub\Expected;
use Craft;
use craft\web\Response;
use juban\newsletter\Newsletter;
use juban\newslettertests\unit\BaseUnitTest;
use Yii;

/**
 * RegisterTest class
 *
 * @author juban
 **/
class NewsletterTest extends BaseUnitTest
{
    public function testSubscribeSuccess()
    {
        $componentInstance = \Craft::$app->get('urlManager');
        \Craft::$app->set('urlManager', Stub::construct(get_class($componentInstance), [], [
            'setRouteParams' => Expected::never()
        ], $this));

        /** @var Response $result */
        $result = $this->runActionWithParams('newsletter/subscribe', [
            'consent' => true,
            'email'   => 'some@email.com',
        ]);

        $this->assertInstanceOf("\craft\web\Response", $result);
        $this->assertEquals(302, $result->statusCode);
    }

    public function testAjaxSubscribeSuccess()
    {
        $componentInstance = \Craft::$app->get('urlManager');
        \Craft::$app->set('urlManager', Stub::construct(get_class($componentInstance), [], [
            'setRouteParams' => Expected::never()
        ], $this));

        \Craft::$app->request->headers->set('Accept', 'application/json');

        /** @var Response $result */
        $result = $this->runActionWithParams('newsletter/subscribe', [
            'consent' => true,
            'email'   => 'some@email.com',
        ]);

        $this->assertInstanceOf("\craft\web\Response", $result);
        $this->assertEquals(200, $result->statusCode);
        $this->assertEquals(\yii\web\Response::FORMAT_JSON, $result->format);
        $this->assertArrayHasKey('success', $result->data);
        $this->assertTrue($result->data['success']);
    }

    public function testSubscribeFailOnMissingConsent()
    {
        $componentInstance = \Craft::$app->get('urlManager');
        \Craft::$app->set('urlManager', Stub::construct(get_class($componentInstance), [], [
            'setRouteParams' => Expected::once()
        ], $this));

        /** @var Response $result */
        $result = $this->runActionWithParams('newsletter/subscribe', [
            'consent' => false,
            'email'   => 'some@email.com',
        ]);

        $this->assertNull($result);
    }

    public function testAjaxSubscribeFailOnMissingConsent()
    {
        $componentInstance = \Craft::$app->get('urlManager');
        \Craft::$app->set('urlManager', Stub::construct(get_class($componentInstance), [], [
            'setRouteParams' => Expected::never()
        ], $this));

        \Craft::$app->request->headers->set('Accept', 'application/json');

        /** @var Response $result */
        $result = $this->runActionWithParams('newsletter/subscribe', [
            'consent' => false,
            'email'   => 'some@email.com',
        ]);

        $this->assertInstanceOf("\craft\web\Response", $result);
        $this->assertEquals(400, $result->statusCode);
        $this->assertEquals(\yii\web\Response::FORMAT_JSON, $result->format);
        $this->assertArrayHasKey('success', $result->data);
        $this->assertFalse($result->data['success']);
        $this->assertArrayHasKey('errors', $result->data);
        $this->assertEquals('Please provide your consent.', $result->data['errors']['consent'][0]);
    }

    public function testSubscribeFailOnInvalidEmail()
    {
        $componentInstance = \Craft::$app->get('urlManager');
        \Craft::$app->set('urlManager', Stub::construct(get_class($componentInstance), [], [
            'setRouteParams' => Expected::once()
        ], $this));

        /** @var Response $result */
        $result = $this->runActionWithParams('newsletter/subscribe', [
            'consent' => true,
            'email'   => 'some@email',
        ]);

        $this->assertNull($result);
    }

    protected function _before()
    {
        parent::_before();

        $this->tester->mockMethods(
            Newsletter::$plugin,
            'adapter',
            [
                'subscribe'            => function (string $email) {
                    return true;
                },
                'getSubscriptionError' => function () {
                    return "Some error";
                }
            ]
        );

        // Set controller namespace to web
        Newsletter::$plugin->controllerNamespace = str_replace('\\console', '', Newsletter::$plugin->controllerNamespace);

        // Force post request
        $_POST[Craft::$app->request->methodParam] = 'post';

        // Disable CSRF validation
        Craft::$app->request->enableCsrfValidation = false;
    }
}
