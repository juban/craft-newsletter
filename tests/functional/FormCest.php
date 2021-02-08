<?php

namespace simplonprod\newslettertests\functional;

use FunctionalTester;
use simplonprod\newsletter\Newsletter;

class FormCest
{
    public $email = 'test@email.com';

    public function _before(FunctionalTester $I)
    {
        Newsletter::$plugin->set('adapter', Newsletter::createAdapter(\simplonprod\newsletter\adapters\Dummy::class, ['someAttribute' => true]));
    }

    // tests
    public function tryToSubscribeSuccessfully(FunctionalTester $I)
    {
        $I->amOnPage('?p=form');
        $I->see('Subscribe');

        $I->submitForm('form', [
            'consent' => true,
            'email'   => $this->email,
        ]);

        $I->see('Thank you.');

    }

    public function trySubscriptionMissingConsent(FunctionalTester $I)
    {
        $I->amOnPage('?p=form');
        $I->see('Subscribe');

        $I->submitForm('form', [
            'consent' => false,
            'email'   => $this->email,
        ]);

        $I->see('Please provide your consent.');

    }

    public function trySubscriptionWithInvalidEmail(FunctionalTester $I)
    {
        $I->amOnPage('?p=form');
        $I->see('Subscribe');

        $I->submitForm('form', [
            'consent' => true,
            'email'   => 'some@email',
        ]);

        $I->see('Please provide a valid email address.');
    }
}
