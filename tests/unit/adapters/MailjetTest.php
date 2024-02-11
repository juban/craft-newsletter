<?php

namespace juban\newslettertests\unit\adapters;

use juban\newsletter\adapters\Mailjet;
use juban\newslettertests\unit\BaseUnitTest;
use Mailjet\Client;
use Mailjet\Resources;
use Mailjet\Response;

class MailjetTest extends BaseUnitTest
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testGetClient(): void
    {
        $mailjetAdapter = new Mailjet([
            'apiKey' => 'myKey',
            'apiSecret' => 'mySecret',
            'listId' => '1234'
        ]);
        $this->assertInstanceOf(Client::class, $mailjetAdapter->getClient());
    }

    public function testSuccessfullSubscriptionOfExistingContact(): void
    {
        $email = 'some@email.com';
        $clientMock = $this->createMock(Client::class);
        $clientMock
            ->expects($this->once())
            ->method('get')
            ->with(Resources::$Contact, ['id' => 'some@email.com'])
            ->willReturn(
                $this->make(
                    Response::class,
                    [
                        'success' => true,
                        'getData' => [['ID' => 12345]]
                    ]
                )
            );
        $clientMock
            ->expects($this->once())
            ->method('post')
            ->with(Resources::$ContactslistManagecontact, [
                'id' => '1234',
                'body' => [
                    'Properties' => "object",
                    'Action' => "addforce",
                    'Email' => 'some@email.com',
                ]
            ])
            ->willReturn(
                $this->make(
                    Response::class,
                    [
                        'success' => true
                    ]
                )
            );

        $mailjetAdapter = $this->_getMailjetAdapterMock($clientMock, ['listId' => '1234']);
        $this->assertTrue($mailjetAdapter->subscribe($email));
    }

    /**
     *
     * @param \PHPUnit\Framework\MockObject\MockObject $clientMock
     * @param array $params
     * @return mixed|\PHPUnit\Framework\MockObject\MockObject
     * @throws \Exception
     */
    private function _getMailjetAdapterMock(
        \PHPUnit\Framework\MockObject\MockObject $clientMock,
        $params = []
    ): \PHPUnit\Framework\MockObject\MockObject {
        $params = array_merge(['getClient' => $clientMock], $params);
        return $this->make(Mailjet::class, $params);
    }

    public function testSuccessFullSubscriptionNewContact(): void
    {
        $email = 'some@email.com';
        $clientMock = $this->createMock(Client::class);
        $clientMock
            ->expects($this->once())
            ->method('get')
            ->with(Resources::$Contact, ['id' => $email])
            ->willReturn(
                $this->make(
                    Response::class,
                    [
                        'success' => false
                    ]
                )
            );
        $clientMock
            ->expects($this->once())
            ->method('post')
            ->with(Resources::$Contact, [
                'body' => [
                    'IsExcludedFromCampaigns' => "false",
                    'Email' => $email
                ]
            ])
            ->willReturn(
                $this->make(
                    Response::class,
                    [
                        'success' => true,
                        'getData' => [['ID' => 1234]]
                    ]
                )
            );
        $mailjetAdapter = $this->_getMailjetAdapterMock($clientMock);
        $this->assertTrue($mailjetAdapter->subscribe($email));
    }

    public function testSuccessFullSubscriptionNewContactWithAdditionalFields(): void
    {
        $email = 'some@email.com';
        $additionalFields = ['firstname' => 'John', 'lastname' => 'Doe'];
        $clientMock = $this->createMock(Client::class);
        $clientMock
            ->expects($this->once())
            ->method('get')
            ->with(Resources::$Contact, ['id' => $email])
            ->willReturn(
                $this->make(
                    Response::class,
                    [
                        'success' => false
                    ]
                )
            );
        $clientMock
            ->expects($this->once())
            ->method('post')
            ->with(Resources::$Contact, [
                'body' => [
                    'IsExcludedFromCampaigns' => "false",
                    'Email' => $email
                ]
            ])
            ->willReturn(
                $this->make(
                    Response::class,
                    [
                        'success' => true,
                        'getData' => [['ID' => 1234]]
                    ]
                )
            );
        $clientMock
            ->expects($this->once())
            ->method('put')
            ->with(Resources::$Contactdata, [
                    'id' => 1234,
                    'body' => [
                        'Data' => [
                            [
                                'Name' => 'firstname',
                                'Value' => 'John'
                            ],
                            [
                                'Name' => 'lastname',
                                'Value' => 'Doe'
                            ],
                        ]
                    ]
                ]
            )
            ->willReturn(
                $this->make(
                    Response::class,
                    [
                        'success' => true,
                    ]
                )
            );
        $mailjetAdapter = $this->_getMailjetAdapterMock($clientMock);
        $this->assertTrue($mailjetAdapter->subscribe($email, $additionalFields));
    }

    public function testFailedSubscriptionWithHandledStatus()
    {
        $email = 'some@email.com';
        $clientMock = $this->createMock(Client::class);
        $clientMock
            ->expects($this->once())
            ->method('get')
            ->with(Resources::$Contact, ['id' => $email])
            ->willReturn(
                $this->make(
                    Response::class,
                    [
                        'success' => false
                    ]
                )
            );
        $clientMock
            ->expects($this->once())
            ->method('post')
            ->with(Resources::$Contact, [
                'body' => [
                    'IsExcludedFromCampaigns' => "false",
                    'Email' => $email
                ]
            ])
            ->willReturn(
                $this->make(
                    Response::class,
                    [
                        'success' => false,
                        'getStatus' => 429
                    ]
                )
            );
        $mailjetAdapter = $this->_getMailjetAdapterMock($clientMock);
        $this->assertFalse($mailjetAdapter->subscribe($email));
        $this->assertEquals(
            'The newsletter service is not available at that time. Please, try again later.',
            $mailjetAdapter->getSubscriptionError()
        );
    }

    public function testFailedSubscriptionWithUnhandledStatus()
    {
        $email = 'some@email.com';
        $clientMock = $this->createMock(Client::class);
        $clientMock
            ->expects($this->once())
            ->method('get')
            ->with(Resources::$Contact, ['id' => $email])
            ->willReturn(
                $this->make(
                    Response::class,
                    [
                        'success' => false
                    ]
                )
            );
        $clientMock
            ->expects($this->once())
            ->method('post')
            ->with(Resources::$Contact, [
                'body' => [
                    'IsExcludedFromCampaigns' => "false",
                    'Email' => $email
                ]
            ])
            ->willReturn(
                $this->make(
                    Response::class,
                    [
                        'success' => false,
                        'getStatus' => 555,
                        'getBody' => [
                            'ErrorInfo' => 'Some info',
                            'ErrorMessage' => 'Some message'
                        ]
                    ]
                )
            );
        $mailjetAdapter = $this->_getMailjetAdapterMock($clientMock);
        $this->assertFalse($mailjetAdapter->subscribe($email));
        $this->assertEquals(
            'The newsletter service is not available at that time. Please, try again later.',
            $mailjetAdapter->getSubscriptionError()
        );
    }

    public function testSuccessfullSubscriptionWithAContactList()
    {
        $email = 'some@email.com';
        $clientMock = $this->createMock(Client::class);
        $clientMock
            ->expects($this->once())
            ->method('get')
            ->with(Resources::$Contact, ['id' => $email])
            ->willReturn(
                $this->make(
                    Response::class,
                    [
                        'success' => true,
                        'getData' => [['ID' => 1234]]
                    ]
                )
            );
        $clientMock
            ->expects($this->once())
            ->method('post')
            ->with(Resources::$ContactslistManagecontact, [
                'id' => 1234,
                'body' => [
                    'Properties' => "object",
                    'Action' => "addforce",
                    'Email' => $email
                ]
            ])
            ->willReturn(
                $this->make(
                    Response::class,
                    [
                        'success' => true
                    ]
                )
            );
        $mailjetAdapter = $this->_getMailjetAdapterMock($clientMock, ['listId' => 1234]);
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
            ->willReturn(
                $this->make(
                    Response::class,
                    [
                        'success' => true,
                        'getData' => [['ID' => 1234]]
                    ]
                )
            );
        $clientMock
            ->expects($this->once())
            ->method('post')
            ->with(Resources::$ContactslistManagecontact, [
                'id' => 1234,
                'body' => [
                    'Properties' => "object",
                    'Action' => "addforce",
                    'Email' => $email
                ]
            ])
            ->willReturn(
                $this->make(
                    Response::class,
                    [
                        'success' => false
                    ]
                )
            );
        $mailjetAdapter = $this->_getMailjetAdapterMock($clientMock, ['listId' => 1234]);
        $this->assertEquals(1234, $mailjetAdapter->listId);
        $this->assertFalse($mailjetAdapter->subscribe($email));
        $this->assertEquals(
            'The newsletter service is not available at that time. Please, try again later.',
            $mailjetAdapter->getSubscriptionError()
        );
    }

    protected function _before()
    {
    }

    protected function _after()
    {
    }
}
