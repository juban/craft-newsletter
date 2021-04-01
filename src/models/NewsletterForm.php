<?php
/**
 * @link https://www.simplonprod.co
 * @copyright Copyright (c) 2021 Simplon.Prod
 */

namespace simplonprod\newsletter\models;


use Craft;
use craft\base\Model;
use simplonprod\googlerecaptcha\GoogleRecaptcha;
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
    public $recaptcha;

    public function rules(): array
    {
        return [
            ['email', 'trim'],
            ['email', 'required', 'message' => Craft::t('newsletter', 'Please provide a valid email address.')],
            ['email', 'email', 'message' => Craft::t('newsletter', 'Please provide a valid email address.')],
            ['consent', 'required', 'requiredValue' => true, 'message' => Craft::t('newsletter', 'Please provide your consent.')],
        ];
    }

    public function subscribe(): bool
    {
        if (!$this->validate()) {
            return false;
        }
        if (class_exists('simplonprod\\googlerecaptcha\\GoogleRecaptcha')) {
            if (!GoogleRecaptcha::$plugin->recaptcha->verify()) {
                $this->addError('recaptcha', Craft::t('newsletter', 'Please prove you are not a robot.'));
                return false;
            }
        }
        // Use newsletter module to register new user
        $newsletterAdapater = Newsletter::$plugin->adapter;
        if (!$newsletterAdapater->subscribe($this->email)) {
            $this->addError('email', $newsletterAdapater->getSubscriptionError());
            return false;
        }
        return true;
    }
}
