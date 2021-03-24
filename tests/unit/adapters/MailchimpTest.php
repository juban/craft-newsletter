<?php


namespace simplonprod\newslettertests\unit\adapters;

use Codeception\Stub\Expected;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MailchimpMarketing\Api\ListsApi;
use MailchimpMarketing\ApiClient;
use simplonprod\newsletter\adapters\Mailchimp;


class MailchimpTest extends \Codeception\Test\Unit
{

    /**
     * @var \UnitTester
     */
    protected $tester;


    private $mailchimp;

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

    public function testGetClient(): void
    {
        $client = $this->mailchimp->getClient();

        self::assertInstanceOf(ApiClient::class, $client);
    }

    public function testUserCanSubscribe(): void
    {
        $email = "mozelle.remy@gmail12345.com";
        $dummyResponse = new Response(404,[],'{}');
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
            ->method('addListMember')
            ->with('123abc', [
                "email_address" => $email,
                "status" => "subscribed",
            ])
            ->willReturn(true);

        $this->mailchimp->setApiClient($client);
        $this->mailchimp->setListApi($listApiClientMock);

        $isSubscribe = $this->mailchimp->subscribe($email);
        self::assertTrue($isSubscribe);
    }

    public function testUserCantSubscribeIfUserAlreadyExist(): void
    {
        $dummyResponse = new Response(404,[],'{}');
        $dummyRequest = new Request('get', '');
        $clientException = new ClientException('', $dummyRequest, $dummyResponse);
        $email = "mozelle.remy@gmail12345.com";

        $client = $this->make(ApiClient::class);

        $listApiClientMock = $this->createMock(ListsApi::class);
        $listApiClientMock
            ->expects($this->once())
            ->method('getListMember')
            ->with('123abc', md5($email))
            ->willThrowException($clientException);

        $listApiClientMock
            ->expects($this->once())
            ->method('addListMember')
            ->with('123abc', [
                "email_address" => $email,
                "status" => "subscribed",
            ])
            ->willThrowException($clientException);

        $this->mailchimp->setApiClient($client);
        $this->mailchimp->setListApi($listApiClientMock);

        $isSubscribe = $this->mailchimp->subscribe($email);
        self::assertFalse($isSubscribe);
    }

    public function testFailedSubscriptionWithHandledStatusBadRequest(): void
    {
        $dummyResponse = new Response(400,[],'{}');
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
            ->method('addListMember')
            ->with('123abc', [
                "email_address" => $email,
                "status" => "subscribed",
            ])
            ->willThrowException($clientException);

        $client = $this->make(ApiClient::class);

        $this->mailchimp->setApiClient($client);
        $this->mailchimp->setListApi($listApiClientMock);

        $isSubscribe = $this->mailchimp->subscribe($email);

        self::assertFalse($isSubscribe);
        self::assertEquals('The newsletter service is not available at that time. Please, try again later.',
            $this->mailchimp->getSubscriptionError());
    }

    public function testFailedSubscriptionWithHandledStatusServerError(): void
    {
        $dummyResponse = new Response(503,[],'{}');
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
            ->method('addListMember')
            ->with('123abc', [
                "email_address" => $email,
                "status" => "subscribed",
            ])
            ->willThrowException($clientException);

        $client = $this->make(ApiClient::class);

        $this->mailchimp->setApiClient($client);
        $this->mailchimp->setListApi($listApiClientMock);

        $isSubscribe = $this->mailchimp->subscribe($email);

        self::assertFalse($isSubscribe);
        self::assertEquals('The newsletter service is not available at that time. Please, try again later.',
            $this->mailchimp->getSubscriptionError());
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
            ->method('addListMember')
            ->with('123abc', [
                "email_address" => $email,
                "status" => "subscribed",
            ])
            ->willThrowException($connectException);

        $client = $this->make(ApiClient::class);

        $this->mailchimp->setApiClient($client);
        $this->mailchimp->setListApi($listApiClientMock);

        $isSubscribe = $this->mailchimp->subscribe($email);

        self::assertFalse($isSubscribe);
        self::assertEquals('The newsletter service is not available at that time. Please, try again later.',
            $this->mailchimp->getSubscriptionError());
    }
}