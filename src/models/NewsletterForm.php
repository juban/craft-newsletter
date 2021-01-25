<?php
/**
 * @link https://www.simplonprod.co
 * @copyright Copyright (c) 2021 Simplon.Prod
 */

namespace simplonprod\newsletter\models;


use Craft;
use craft\base\Model;
use simplonprod\newsletter\Newsletter;

/**
 * NewsletterForm class
 *
 * @author albanjubert
 **/
class NewsletterForm extends Model
{
    public $email;
    public $consent;

    public function rules()
    {
        return [
            ['email', 'required', 'message' => Craft::t('newsletter', 'Please provide a valid email address.')],
            ['email', 'trim'],
            ['consent', 'required', 'message' => Craft::t('newsletter', 'Please provide your consent.')],
            ['email', 'email', 'message' => Craft::t('newsletter', 'Please provide a valid email address.')]
        ];
    }

    public function subscribe(): bool
    {
        if (!$this->validate()) {
            return false;
        }
        // Use newsletter module to register new user
        $newsletterAdapater = Newsletter::$plugin->getAdapter();
        if (!$newsletterAdapater->subscribe($this->email)) {
            $this->addError('email', $newsletterAdapater->getSubscriptionError());
            return false;
        }
        return true;
    }
}
