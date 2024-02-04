<?php


namespace juban\newslettertests\unit\adapters;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use juban\newsletter\adapters\Mailchimp;
use MailchimpMarketing\Api\ListsApi;
use MailchimpMarketing\ApiClient;


class MailchimpTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $tester;


    private $mailchimp;

    public function testGetClient(): void
    {
        $client = $this->mailchimp->getClient();

        self::assertInstanceOf(ApiClient::class, $client);
    }

    public function testUserCanSubscribe(): void
    {
        $email = "mozelle.remy@gmail12345.com";
        $dummyResponse = new Response(404, [], '{}');
        $dummyRequest = new Request('get', '');
        $clientException = new ClientException('', $dummyRequest, $dummyResponse);
        $client = $this->make(ApiClient::class);

        $listApiClientMock = $this->createMock(ListsApi::class);

        $listApiClientMock
            ->expects($this->once())
            ->method('getListMember')
            ->with('123abc', md5($email))
            ->willThrowException($clientException);

        $listApiClientMock
            ->expects($this->once())
            ->method('setListMember')
            ->with(
                '123abc',
                $email,
                [
                    "email_address" => $email,
                    "status_if_new" => "subscribed",
                    "status" => "subscribed",
                    "double_optin" => true,
                    "merge_fields" => []
                ]
            )
            ->willReturn(true);

        $this->mailchimp->setApiClient($client);
        $this->mailchimp->setListApi($listApiClientMock);

        $isSubscribe = $this->mailchimp->subscribe($email);
        self::assertTrue($isSubscribe);
    }

    public function testUserCanSubscribeIfUserAlreadyExist(): void
    {
        $dummyResponse = new Response(404, [], '{}');
        $dummyRequest = new Request('get', '');
        $clientException = new ClientException('', $dummyRequest, $dummyResponse);
        $email = "mozelle.remy@gmail12345.com";

        $client = $this->make(ApiClient::class);

        $listApiClientMock = $this->createMock(ListsApi::class);
        $listApiClientMock
            ->expects($this->once())
            ->method('getListMember')
            ->with('123abc', md5($email))
            ->willReturn(true);

        $listApiClientMock
            ->expects($this->never())
            ->method('setListMember')
            ->with(
                '123abc',
                $email,
                [
                    "email_address" => $email,
                    "status_if_new" => "subscribed",
                    "status" => "subscribed",
                    "double_optin" => true,
                    "merge_fields" => []
                ]
            )
            ->willThrowException($clientException);

        $this->mailchimp->setApiClient($client);
        $this->mailchimp->setListApi($listApiClientMock);

        $isSubscribe = $this->mailchimp->subscribe($email);
        self::assertTrue($isSubscribe);
    }

    public function testFailedSubscriptionWithHandledStatusBadRequest(): void
    {
        $dummyResponse = new Response(400, [], '{}');
        $dummyRequest = new Request('get', '');
        $clientException = new ClientException('', $dummyRequest, $dummyResponse);

        $email = "mozelle.remy@gmail12345.com";
        $listApiClientMock = $this->createMock(ListsApi::class);
        $listApiClientMock
            ->expects($this->once())
            ->method('getListMember')
            ->with('123abc', md5($email))
            ->willThrowException($clientException);

        $listApiClientMock
            ->expects($this->once())
            ->method('setListMember')
            ->with(
                '123abc',
                $email,
                [
                    "email_address" => $email,
                    "status_if_new" => "subscribed",
                    "status" => "subscribed",
                    "double_optin" => true,
                    "merge_fields" => []
                ]
            )
            ->willThrowException($clientException);

        $client = $this->make(ApiClient::class);

        $this->mailchimp->setApiClient($client);
        $this->mailchimp->setListApi($listApiClientMock);

        $isSubscribe = $this->mailchimp->subscribe($email);

        self::assertFalse($isSubscribe);
        self::assertEquals(
            'The newsletter service is not available at that time. Please, try again later.',
            $this->mailchimp->getSubscriptionError()
        );
    }

    public function testFailedSubscriptionWithHandledStatusServerError(): void
    {
        $dummyResponse = new Response(503, [], '{}');
        $dummyRequest = new Request('get', '');
        $clientException = new ClientException('', $dummyRequest, $dummyResponse);

        $email = "mozelle.remy@gmail12345.com";
        $listApiClientMock = $this->createMock(ListsApi::class);
        $listApiClientMock
            ->expects($this->once())
            ->method('getListMember')
            ->with('123abc', md5($email))
            ->willThrowException($clientException);

        $listApiClientMock
            ->expects($this->once())
            ->method('setListMember')
            ->with(
                '123abc',
                $email,
                [
                    "email_address" => $email,
                    "status_if_new" => "subscribed",
                    "status" => "subscribed",
                    "double_optin" => true,
                    "merge_fields" => []
                ]
            )
            ->willThrowException($clientException);

        $client = $this->make(ApiClient::class);

        $this->mailchimp->setApiClient($client);
        $this->mailchimp->setListApi($listApiClientMock);

        $isSubscribe = $this->mailchimp->subscribe($email);

        self::assertFalse($isSubscribe);
        self::assertEquals(
            'The newsletter service is not available at that time. Please, try again later.',
            $this->mailchimp->getSubscriptionError()
        );
    }

    public function testFailedSubscriptionWithThrowConnectException(): void
    {
        $dummyRequest = new Request('get', '');
        $connectException = new ConnectException('', $dummyRequest);

        $email = "mozelle.remy@gmail12345.com";

        $listApiClientMock = $this->createMock(ListsApi::class);
        $listApiClientMock
            ->expects($this->once())
            ->method('getListMember')
            ->with('123abc', md5($email))
            ->willThrowException($connectException);

        $listApiClientMock
            ->expects($this->once())
            ->method('setListMember')
            ->with(
                '123abc',
                $email,
                [
                    "email_address" => $email,
                    "status_if_new" => "subscribed",
                    "status" => "subscribed",
                    "double_optin" => true,
                    "merge_fields" => []
                ]
            )
            ->willThrowException($connectException);

        $client = $this->make(ApiClient::class);

        $this->mailchimp->setApiClient($client);
        $this->mailchimp->setListApi($listApiClientMock);

        $isSubscribe = $this->mailchimp->subscribe($email);

        self::assertFalse($isSubscribe);
        self::assertEquals(
            'The newsletter service is not available at that time. Please, try again later.',
            $this->mailchimp->getSubscriptionError()
        );
    }

    protected function _before()
    {
        $this->mailchimp = new Mailchimp();
        $this->mailchimp->apiKey = 'apikey';
        $this->mailchimp->serverPrefix = 'serverPrefix';
        $this->mailchimp->listId = '123abc';
    }

    protected function _after()
    {
    }
}
