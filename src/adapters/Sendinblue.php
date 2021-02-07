<?php


namespace simplonprod\newsletter\adapters;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use GuzzleHttp\Client;
use SendinBlue\Client\Api\ContactsApi;
use SendinBlue\Client\ApiException;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Model\CreateContact;
use yii\helpers\Json;
use yii\helpers\VarDumper;

/**
 *
 * @property-read ContactsApi $clientContactApi
 * @property-read mixed $settingsHtml
 * @property-read null|string $subscriptionError
 */
class Sendinblue extends BaseNewsletterAdapter
{
    public $apiKey;
    public $listId;
    private $_errorMessage;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Sendinblue';
    }

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['parser'] = [
            'class'      => EnvAttributeParserBehavior::class,
            'attributes' => [
                'apiKey',
                'listId'
            ],
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'apiKey' => Craft::t('newsletter', 'API Key'),
            'listId' => Craft::t('newsletter', 'Contact List ID'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('newsletter/newsletterAdapters/Sendinblue/settings', [
            'adapter' => $this
        ]);
    }

    public function subscribe(string $email): bool
    {
        $clientContactApi = $this->getClientContactApi();
        $listId = Craft::parseEnv($this->listId);

        if (!$this->_contactExist($email, $clientContactApi)) {

            if (!empty($this->listId) && (int)$listId !== 0) {
                return $this->_registerContactToList($email, $clientContactApi, (int)$listId);
            }

            return $this->_registerContact($email, $clientContactApi);
        }

        return true;
    }

    public function getClientContactApi(): ContactsApi
    {
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', Craft::parseEnv($this->apiKey));

        return new ContactsApi(
            new Client(),
            $config
        );
    }

    private function _contactExist(string $email, ContactsApi $client): bool
    {
        try {
            $client->getContactInfo($email);
            return true;
        } catch (ApiException $apiException) {
            return $this->_getErrorMessage($apiException);
        }
    }

    private function _getErrorMessage(ApiException $apiException): bool
    {
        $errorMessage = Craft::t('newsletter', 'The newsletter service is not available at that time. Please, try again later.');

        if ($apiException->getCode() === 401) {
            Craft::error('Sendin blue You have not been authenticated. Make sure the provided api-key is correct. ' . VarDumper::dumpAsString($apiException->getMessage()), __METHOD__);
        } elseif ($apiException->getCode() === 400) {
            Craft::error('Sendin blue Request is invalid. Check the error code in JSON (400)' . VarDumper::dumpAsString($apiException->getMessage()), __METHOD__);
        } elseif ($apiException->getCode() === 403) {
            Craft::error('Sendin blue You do not have the rights to access the resource (403).' . VarDumper::dumpAsString($apiException->getMessage()), __METHOD__);
        } elseif ($apiException->getCode() === 404) {
            Craft::error('Sendin blue resource was not found (404).' . VarDumper::dumpAsString($apiException->getMessage()), __METHOD__);
        } elseif ($apiException->getCode() === 405) {
            Craft::error('Sendin blue method The verb you\'re using is not allowed for this endpoint (405).' . VarDumper::dumpAsString($apiException->getMessage()), __METHOD__);
        } elseif ($apiException->getCode() === 406) {
            Craft::error('Sendin blue Make sure the value is application/json only and not empty (406).' . VarDumper::dumpAsString($apiException->getMessage()), __METHOD__);
        } elseif ($apiException->getCode() === 429) {
            Craft::error('Sendin blue The expected rate limit is exceeded. (429).' . VarDumper::dumpAsString($apiException->getMessage()), __METHOD__);
        } elseif ($apiException->getCode() === 500) {
            Craft::error('Sendin blue internal server error (500).' . VarDumper::dumpAsString($apiException->getMessage()), __METHOD__);
        } else {
            $body = Json::decode($apiException->getResponseBody(), false);
            $errorMessage = Craft::t('newsletter', 'An error has occurred : {errorMessage}.', ['errorMessage' => $body->message ?? '']);
        }

        $this->_errorMessage = $errorMessage;

        return false;
    }

    private function _registerContactToList(string $email, ContactsApi $clientContactApi, int $listId): bool
    {
        try {
            $contact = new CreateContact();
            $contact['email'] = $email;
            $contact['listIds'] = [$listId];
            $clientContactApi->createContact($contact);
            return true;
        } catch (ApiException $apiException) {
            return $this->_getErrorMessage($apiException);
        }
    }

    private function _registerContact(string $email, ContactsApi $clientContactApi): bool
    {
        try {
            $contact = new CreateContact();
            $contact['email'] = $email;
            $clientContactApi->createContact($contact);
            return true;
        } catch (ApiException $apiException) {
            return $this->_getErrorMessage($apiException);
        }
    }

    public function getSubscriptionError(): ?string
    {
        return $this->_errorMessage;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['apiKey'], 'trim'];
        $rules[] = [['apiKey'], 'required'];
        $rules[] = [['listId'], 'integer'];
        return $rules;
    }
}
