<?php

namespace juban\newslettertests\unit\adapters;

use Codeception\Stub\Expected;
use Brevo\Client\Api\ContactsApi;
use Brevo\Client\ApiException;
use Brevo\Client\Model\GetExtendedContactDetails;
use juban\newsletter\adapters\Brevo;


class BrevoTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    private $brevo;

    public function testGetClientContactApi(): void
    {
        $contactApi = $this->make(ContactsApi::class);
        $this->brevo->setClientContactApi($contactApi);

        self::assertInstanceOf(ContactsApi::class, $this->brevo->clientContactApi);
    }

    public function testUserAlreadyExistedCantSubscribeTwice(): void
    {
        $email = "mozelle.remy@gmail12345.com";

        $contactApi = $this->make(ContactsApi::class, [
            'getContactInfo' => new GetExtendedContactDetails(),
            'createContact'  => Expected::never(),
        ]);
        $this->brevo->setClientContactApi($contactApi);
        $isSubscribe = $this->brevo->subscribe($email);

        self::assertTrue($isSubscribe);
    }

    public function testUserNotExist(): void
    {
        $email = "mozelle.remy@gmail12345.com";

        $contactApi = $this->make(ContactsApi::class, [
            'getContactInfo' => static function () {
                throw new ApiException();
            },
            'createContact'  => static function ($contact) {
                self::assertNull($contact['listIds']);
                self::assertEquals('mozelle.remy@gmail12345.com', $contact['email']);
            }
        ]);

        $this->brevo->setClientContactApi($contactApi);
        $isSubscribe = $this->brevo->subscribe($email);

        self::assertTrue($isSubscribe);
    }

    public function testUserCanSubscribeToTheList(): void
    {
        $email = "mozelle.remy@gmail12345.com";
        $this->brevo->listId = 22;

        $contactApi = $this->make(ContactsApi::class, [
            'getContactInfo' => static function () {
                throw new ApiException();
            },
            'createContact'  => static function ($contact) {
                self::assertEquals(22, $contact['listIds'][0]);
                self::assertIsNumeric($contact['listIds'][0]);
                self::assertEquals('mozelle.remy@gmail12345.com', $contact['email']);
            },
        ]);

        $this->brevo->setClientContactApi($contactApi);
        $isSubscribe = $this->brevo->subscribe($email);

        self::assertTrue($isSubscribe);
    }

    public function testUserCanSubscribeToTheListWithDoi(): void
    {
        $email = "mozelle.remy@gmail12345.com";
        $this->brevo->doi = true;
        $this->brevo->doiTemplateId = 156;
        $this->brevo->listId = 35;
        $this->brevo->doiRedirectionUrl = 'https://www.somedomain.com/return-url';

        $contactApi = $this->make(ContactsApi::class, [
            'getContactInfo'   => static function () {
                throw new ApiException();
            },
            'createDoiContact' => static function ($contact) {
                self::assertEquals(35, $contact['includeListIds'][0]);
                self::assertIsNumeric($contact['includeListIds'][0]);
                self::assertEquals('mozelle.remy@gmail12345.com', $contact['email']);
                self::assertEquals(156, $contact['templateId']);
                self::assertIsNumeric($contact['templateId']);
                self::assertEquals('https://www.somedomain.com/return-url', $contact['redirectionUrl']);
            },
        ]);

        $this->brevo->setClientContactApi($contactApi);
        $isSubscribe = $this->brevo->subscribe($email);

        self::assertTrue($isSubscribe);
    }

    public function testExistingUserCanSubscribeToList(): void
    {
        $email = "mozelle.remy@gmail12345.com";
        $this->brevo->listId = 75;

        $contactApi = $this->make(ContactsApi::class, [
            'getContactInfo' => static fn() => new GetExtendedContactDetails(['email' => "mozelle.remy@gmail12345.com"]),
            'updateContact'  => static function ($email, $contact) {
                self::assertEquals(75, $contact['listIds'][0]);
                self::assertIsNumeric($contact['listIds'][0]);
            },
        ]);

        $this->brevo->setClientContactApi($contactApi);
        $isSubscribe = $this->brevo->subscribe($email);

        self::assertTrue($isSubscribe);
    }

    public function testFailedSubscriptionWithHandledStatusBadRequest(): void
    {
        $email = "mozelle.remy@gmail12345.com";

        $contactApi = $this->make(ContactsApi::class, [
            'getContactInfo' => static function () {
                throw new ApiException("", 400);
            },
            'createContact'  => static function () {
                throw new ApiException("", 400);
            }
        ]);

        $this->brevo->setClientContactApi($contactApi);
        $isSubscribe = $this->brevo->subscribe($email);

        self::assertFalse($isSubscribe);
        self::assertEquals('The newsletter service is not available at that time. Please, try again later.', $this->brevo->getSubscriptionError());
    }

    public function testFailedSubscriptionWithHandledStatusServerError(): void
    {
        $email = "mozelle.remy@gmail12345.com";

        $contactApi = $this->make(ContactsApi::class, [
            'getContactInfo' => static function () {
                throw new ApiException("", 503);
            },
            'createContact'  => static function () {
                throw new ApiException("", 503);
            }
        ]);

        $this->brevo->setClientContactApi($contactApi);
        $isSubscribe = $this->brevo->subscribe($email);

        self::assertFalse($isSubscribe);
        self::assertEquals('The newsletter service is not available at that time. Please, try again later.', $this->brevo->getSubscriptionError());
    }

    protected function _before()
    {
        $this->brevo = new Brevo();
        $this->brevo->apiKey = 'randomKey';
    }

    protected function _after()
    {
    }
}
