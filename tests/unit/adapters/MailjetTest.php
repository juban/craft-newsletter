<?php

namespace simplonprod\newslettertests\unit\adapters;

use AspectMock\Test;
use Mailjet\Client;
use Mailjet\Resources;
use Mailjet\Response;
use simplonprod\newsletter\adapters\Mailjet;
use simplonprod\newslettertests\unit\BaseUnitTest;

class MailjetTest extends BaseUnitTest
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testGetClient()
    {
        $mailjetAdapter = new Mailjet([
            'apiKey'    => 'myKey',
            'apiSecret' => 'mySecret'
        ]);
        $this->assertInstanceOf(Client::class, $mailjetAdapter->getClient());
    }

    public function testSuccessfullSubscriptionOfExistingContact()
    {
        $clientMock = $this->createMock(Client::class);
        $clientMock
            ->expects($this->once())
            ->method('get')
            ->with(Resources::$Contact, ['id' => 'some@email.com'])
            ->willReturn($this->make(Response::class,
                [
                    'success' => true
                ])
            );
        $clientMock
            ->expects($this->never())
            ->method('post');
        $mailjetAdapter = $this->make(new Mailjet(),
            [
                'getClient' => $clientMock
            ]
        );
        $this->assertTrue($mailjetAdapter->subscribe('some@email.com'));
    }

    public function testSuccessfullSubscriptionNewContact()
    {
        $email = 'some@email.com';
        $clientMock = $this->createMock(Client::class);
        $clientMock
            ->expects($this->once())
            ->method('get')
            ->with(Resources::$Contact, ['id' => $email])
            ->willReturn($this->make(Response::class,
                [
                    'success' => false
                ])
            );
        $clientMock
            ->expects($this->once())
            ->method('post')
            ->with(Resources::$Contact, [
                'body' => [
                    'IsExcludedFromCampaigns' => "false",
                    'Email'                   => $email
                ]
            ])
            ->willReturn($this->make(Response::class,
                [
                    'success' => true
                ])
            );
        $mailjetAdapter = $this->make(new Mailjet(),
            [
                'getClient' => $clientMock
            ]
        );
        $this->assertTrue($mailjetAdapter->subscribe($email));
    }

    public function testFailedSubscriptionWithHandledStatus()
    {
        $email = 'some@email.com';
        $clientMock = $this->createMock(Client::class);
        $clientMock
            ->expects($this->once())
            ->method('get')
            ->with(Resources::$Contact, ['id' => $email])
            ->willReturn($this->make(Response::class,
                [
                    'success' => false
                ])
            );
        $clientMock
            ->expects($this->once())
            ->method('post')
            ->with(Resources::$Contact, [
                'body' => [
                    'IsExcludedFromCampaigns' => "false",
                    'Email'                   => $email
                ]
            ])
            ->willReturn($this->make(Response::class,
                [
                    'success'   => false,
                    'getStatus' => 429
                ])
            );
        $mailjetAdapter = $this->make(new Mailjet(),
            [
                'getClient' => $clientMock
            ]
        );
        $this->assertFalse($mailjetAdapter->subscribe($email));
        $this->assertEquals('The newsletter service is not available at that time. Please, try again later.', $mailjetAdapter->getSubscriptionError());
    }

    public function testFailedSubscriptionWithUnhandledStatus()
    {
        $email = 'some@email.com';
        $clientMock = $this->createMock(Client::class);
        $clientMock
            ->expects($this->once())
            ->method('get')
            ->with(Resources::$Contact, ['id' => $email])
            ->willReturn($this->make(Response::class,
                [
                    'success' => false
                ])
            );
        $clientMock
            ->expects($this->once())
            ->method('post')
            ->with(Resources::$Contact, [
                'body' => [
                    'IsExcludedFromCampaigns' => "false",
                    'Email'                   => $email
                ]
            ])
            ->willReturn($this->make(Response::class,
                [
                    'success'   => false,
                    'getStatus' => 555,
                    'getBody'   => [
                        'ErrorInfo'    => 'Some info',
                        'ErrorMessage' => 'Some message'
                    ]
                ])
            );
        $mailjetAdapter = $this->make(new Mailjet(),
            [
                'getClient' => $clientMock
            ]
        );
        $this->assertFalse($mailjetAdapter->subscribe($email));
        $this->assertEquals('The newsletter service is not available at that time. Please, try again later.', $mailjetAdapter->getSubscriptionError());
    }

    public function testSuccessfullSubscriptionWithAContactList()
    {
        $email = 'some@email.com';
        $clientMock = $this->createMock(Client::class);
        $clientMock
            ->expects($this->once())
            ->method('get')
            ->with(Resources::$Contact, ['id' => $email])
            ->willReturn($this->make(Response::class,
                [
                    'success' => true
                ])
            );
        $clientMock
            ->expects($this->once())
            ->method('post')
            ->with(Resources::$ContactslistManagecontact, [
                'id'   => 1234,
                'body' => [
                    'Properties' => "object",
                    'Action'     => "addnoforce",
                    'Email'      => $email
                ]
            ])
            ->willReturn($this->make(Response::class,
                [
                    'success' => true
                ])
            );
        $mailjetAdapter = $this->make(Mailjet::class,
            [
                'listId'    => 1234,
                'getClient' => $clientMock
            ]
        );
        $this->assertEquals(1234, $mailjetAdapter->listId);
        $this->assertTrue($mailjetAdapter->subscribe($email));
    }

    public function testFailedSubscriptionWithAContactList()
    {
        $email = 'some@email.com';
        $clientMock = $this->createMock(Client::class);
        $clientMock
            ->expects($this->once())
            ->method('get')
            ->with(Resources::$Contact, ['id' => $email])
            ->willReturn($this->make(Response::class,
                [
                    'success' => true
                ])
            );
        $clientMock
            ->expects($this->once())
            ->method('post')
            ->with(Resources::$ContactslistManagecontact, [
                'id'   => 1234,
                'body' => [
                    'Properties' => "object",
                    'Action'     => "addnoforce",
                    'Email'      => $email
                ]
            ])
            ->willReturn($this->make(Response::class,
                [
                    'success' => false
                ])
            );
        $mailjetAdapter = $this->make(Mailjet::class,
            [
                'listId'    => 1234,
                'getClient' => $clientMock
            ]
        );
        $this->assertEquals(1234, $mailjetAdapter->listId);
        $this->assertFalse($mailjetAdapter->subscribe($email));
        $this->assertEquals('The newsletter service is not available at that time. Please, try again later.', $mailjetAdapter->getSubscriptionError());
    }

    protected function _before()
    {

    }

    protected function _after()
    {
    }
}
