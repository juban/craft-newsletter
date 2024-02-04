<?php

namespace juban\newsletter\models;

use Craft;
use craft\base\Model;
use juban\newsletter\Newsletter;

/**
 * NewsletterForm class
 *
 * @author juban
 **/
class NewsletterForm extends Model
{
    public $email;
    public $consent;
    public $additionalFields;

    public function rules(): array
    {
        return [
            ['email', 'trim'],
            ['email', 'required', 'message' => Craft::t('newsletter', 'Please provide a valid email address.')],
            ['email', 'email', 'message' => Craft::t('newsletter', 'Please provide a valid email address.')],
            ['consent', 'required', 'requiredValue' => true, 'message' => Craft::t('newsletter', 'Please provide your consent.')],
            ['additionalFields', 'default', 'value' => []],
        ];
    }

    public function subscribe(): bool
    {
        if (!$this->validate()) {
            return false;
        }
        // Use newsletter module to register new user
        $newsletterAdapater = Newsletter::$plugin->adapter;
        if (!$newsletterAdapater->subscribe($this->email, $this->additionalFields)) {
            $this->addError('email', $newsletterAdapater->getSubscriptionError());
            return false;
        }
        return true;
    }
}
