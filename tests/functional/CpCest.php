<?php

namespace simplonprod\newslettertests\functional;

use Craft;
use craft\elements\User;
use FunctionalTester;
use simplonprod\newsletter\adapters\Mailjet;

class CpCest
{
    /**
     * @var string
     */
    public $cpTrigger;

    /**
     * @var
     */
    public $currentUser;


    public function _before(FunctionalTester $I)
    {
        $this->currentUser = User::find()
            ->admin()
            ->one();

        $I->amLoggedInAs($this->currentUser);
        $this->cpTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;

        Craft::$app->setEdition(Craft::Pro);
    }

    // tests
    public function tryToShowControlPannel(FunctionalTester $I)
    {
        $I->amOnPage('/' . $this->cpTrigger . '/settings/plugins/newsletter');
        $I->seeResponseCodeIs(200);
        $I->see('Service type');
    }

    public function tryToSaveSettingFromControlPannel(FunctionalTester $I)
    {
        $I->amOnPage('/' . $this->cpTrigger . '/settings/plugins/newsletter');
        $I->submitForm('#main-form', [
            'settings' => [
                'adapterType' => Mailjet::class,
                'adapterSettings' => [
                    Mailjet::class => [
                        'apiKey' => 'azertyuiop',
                        'apiSecret' => 'qsdfghjklm'
                    ]
                ]
            ]
        ]);
        $I->seeResponseCodeIs(300);

    }
}
