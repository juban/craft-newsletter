<?php
/**
 * @link https://www.simplonprod.co
 * @copyright Copyright (c) 2021 Simplon.Prod
 */

namespace simplonprod\newsletter\models;


use Craft;
use craft\base\Model;

/**
 * NewsletterSettings class
 *
 * @author albanjubert
 **/
class Settings extends Model
{
    public $adapterType;
    public $adapterTypeSettings = [];

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
