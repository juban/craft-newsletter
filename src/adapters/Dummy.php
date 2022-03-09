<?php
/**
 * @link https://www.simplonprod.co
 * @copyright Copyright (c) 2021 Simplon.Prod
 */

namespace simplonprod\newsletter\adapters;

use Craft;

/**
 * Dummy class
 * This class is intended as a base for concret adapters
 *
 * @author albanjubert
 **/
class Dummy extends BaseNewsletterAdapter
{
    public $someAttribute;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Dummy';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'someAttribute' => Craft::t('newsletter', 'Some attribute'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('newsletter/newsletterAdapters/Dummy/settings', [
            'adapter' => $this,
        ]);
    }

    /**
     * @param string $email
     * @return bool
     */
    public function subscribe(string $email): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getSubscriptionError(): string
    {
        return "Some error";
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['someAttribute'], 'trim'];
        $rules[] = [['someAttribute'], 'required'];
        return $rules;
    }
}
