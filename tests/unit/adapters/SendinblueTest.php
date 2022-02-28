<?php

namespace simplonprod\newslettertests\unit\adapters;

use Codeception\Stub\Expected;
use SendinBlue\Client\Api\ContactsApi;
use SendinBlue\Client\ApiException;
use SendinBlue\Client\Model\GetExtendedContactDetails;
use simplonprod\newsletter\adapters\Sendinblue;


class SendinblueTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    private $sendinblue;

    public function testGetClientContactApi(): void
    {
        $contactApi = $this->make(ContactsApi::class);
        $this->sendinblue->setClientContactApi($contactApi);

        self::assertInstanceOf(ContactsApi::class, $this->sendinblue->clientContactApi);
    }

    public function testUserAlreadyExistedCantSubscribeTwice(): void
    {
        $email = "mozelle.remy@gmail12345.com";

        $contactApi = $this->make(ContactsApi::class, [
            'getContactInfo' => new GetExtendedContactDetails(),
            'createContact'  => Expected::never(),
        ]);
        $this->sendinblue->setClientContactApi($contactApi);
        $isSubscribe = $this->sendinblue->subscribe($email);

        self::assertTrue($isSubscribe);
    }

    public function testUserNotExist(): void
    {
        $email = "mozelle.remy@gmail12345.com";

        $contactApi = $this->make(ContactsApi::class, [
            'getContactInfo' => function () {
                throw new ApiException();
            },
            'createContact'  => function ($contact) {
                self::assertNull($contact['listIds']);
                self::assertEquals('mozelle.remy@gmail12345.com', $contact['email']);
            }
        ]);

        $this->sendinblue->setClientContactApi($contactApi);
        $isSubscribe = $this->sendinblue->subscribe($email);

        self::assertTrue($isSubscribe);
    }

    public function testUserCanSubscribeToTheList(): void
    {
        $email = "mozelle.remy@gmail12345.com";
        $this->sendinblue->listId = 22;

        $contactApi = $this->make(ContactsApi::class, [
            'getContactInfo' => function () {
                throw new ApiException();
            },
            'createContact'  => function ($contact) {
                self::assertEquals(22, $contact['listIds'][0]);
                self::assertIsNumeric($contact['listIds'][0]);
                self::assertEquals('mozelle.remy@gmail12345.com', $contact['email']);
            },
        ]);

        $this->sendinblue->setClientContactApi($contactApi);
        $isSubscribe = $this->sendinblue->subscribe($email);

        self::assertTrue($isSubscribe);
    }

    public function testUserCanSubscribeToTheListWithDoi(): void
    {
        $email = "mozelle.remy@gmail12345.com";
        $this->sendinblue->doi = true;
        $this->sendinblue->doiTemplateId = 156;
        $this->sendinblue->listId = 35;
        $this->sendinblue->doiRedirectionUrl = 'https://www.somedomain.com/return-url';

        $contactApi = $this->make(ContactsApi::class, [
            'getContactInfo'   => function () {
                throw new ApiException();
            },
            'createDoiContact' => function ($contact) {
                self::assertEquals(35, $contact['includeListIds'][0]);
                self::assertIsNumeric($contact['includeListIds'][0]);
                self::assertEquals('mozelle.remy@gmail12345.com', $contact['email']);
                self::assertEquals(156, $contact['templateId']);
                self::assertIsNumeric($contact['templateId']);
                self::assertEquals('https://www.somedomain.com/return-url', $contact['redirectionUrl']);
            },
        ]);

        $this->sendinblue->setClientContactApi($contactApi);
        $isSubscribe = $this->sendinblue->subscribe($email);

        self::assertTrue($isSubscribe);
    }

    public function testExistingUserCanSubscribeToList(): void
    {
        $email = "mozelle.remy@gmail12345.com";
        $this->sendinblue->listId = 75;

        $contactApi = $this->make(ContactsApi::class, [
            'getContactInfo' => function () {
                return new GetExtendedContactDetails(['email' => "mozelle.remy@gmail12345.com"]);
            },
            'updateContact'  => function ($email, $contact) {
                self::assertEquals(75, $contact['listIds'][0]);
                self::assertIsNumeric($contact['listIds'][0]);
            },
        ]);

        $this->sendinblue->setClientContactApi($contactApi);
        $isSubscribe = $this->sendinblue->subscribe($email);

        self::assertTrue($isSubscribe);
    }

    public function testFailedSubscriptionWithHandledStatusBadRequest(): void
    {
        $email = "mozelle.remy@gmail12345.com";

        $contactApi = $this->make(ContactsApi::class, [
            'getContactInfo' => function () {
                throw new ApiException("", 400);
            },
            'createContact'  => function () {
                throw new ApiException("", 400);
            }
        ]);

        $this->sendinblue->setClientContactApi($contactApi);
        $isSubscribe = $this->sendinblue->subscribe($email);

        self::assertFalse($isSubscribe);
        self::assertEquals('The newsletter service is not available at that time. Please, try again later.', $this->sendinblue->getSubscriptionError());
    }

    public function testFailedSubscriptionWithHandledStatusServerError(): void
    {
        $email = "mozelle.remy@gmail12345.com";

        $contactApi = $this->make(ContactsApi::class, [
            'getContactInfo' => function () {
                throw new ApiException("", 503);
            },
            'createContact'  => function () {
                throw new ApiException("", 503);
            }
        ]);

        $this->sendinblue->setClientContactApi($contactApi);
        $isSubscribe = $this->sendinblue->subscribe($email);

        self::assertFalse($isSubscribe);
        self::assertEquals('The newsletter service is not available at that time. Please, try again later.', $this->sendinblue->getSubscriptionError());
    }

    protected function _before()
    {
        $this->sendinblue = new Sendinblue();
        $this->sendinblue->apiKey = 'randomKey';
    }

    protected function _after()
    {
    }
}
