<?php

namespace juban\newsletter\models;

use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;

/**
 * NewsletterSettings class
 *
 * @author juban
 **/
class Settings extends Model
{
    public $adapterType;
    public $adapterTypeSettings = [];
    public $recaptchaEnabled = true;

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['parser'] = [
            'class' => EnvAttributeParserBehavior::class,
            'attributes' => [
                'recaptchaEnabled',
            ],
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'adapterType' => Craft::t('newsletter', 'Service Type'),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = ['adapterType', 'required'];

        return $rules;
    }
}
