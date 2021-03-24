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
    private $contactsApi;

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
            'class' => EnvAttributeParserBehavior::class,
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
        $listId = $this->listId ?? Craft::parseEnv($this->listId);
        $listId = (int)$listId;

        if (!$this->_contactExist($email, $clientContactApi)) {
            if ($listId !== 0) {
                return $this->_registerContactToList($email, $clientContactApi);
            }

            return $this->_registerContact($email, $clientContactApi);
        }

        return true;
    }

    public function getClientContactApi(): ContactsApi
    {
        if (!$this->contactsApi) {
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', Craft::parseEnv($this->apiKey));

            $this->contactsApi = new ContactsApi(
                new Client(),
                $config
            );
        }

        return $this->contactsApi;
    }

    public function setClientContactApi(ContactsApi $contactsApi): void
    {
        $this->contactsApi = $contactsApi;
    }

    private function _contactExist(string $email, ContactsApi $client): bool
    {
        try {
            $client->getContactInfo($email);
            return true;
        } catch (ApiException $apiException) {
            $this->_errorMessage = $this->_getErrorMessage($apiException);
            return false;
        }
    }

    private function _registerContactToList(string $email, ContactsApi $clientContactApi): bool
    {
        try {
            $listId = (int)$this->listId;
            $contact = new CreateContact();
            $contact['email'] = $email;
            $contact['listIds'] = [$listId];
            $clientContactApi->createContact($contact);
            return true;
        } catch (ApiException $apiException) {
            $this->_errorMessage = $this->_getErrorMessage($apiException);
            return false;
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
            $this->_errorMessage = $this->_getErrorMessage($apiException);
            return false;
        }
    }

    private function _getErrorMessage(ApiException $apiException): string
    {
        $errorLogMessages = [
            400 => 'Sendinblue request is invalid. Check the error code in JSON (400).',
            401 => 'Sendinblue authentication error (401). Make sure the provided api-key is correct.',
            403 => 'Sendinblue resource access error (403).',
            404 => 'Sendinblue resource was not found (404).',
            405 => 'Sendinblue verb is not allowed for this endpoint (405).',
            406 => 'Sendinblue empty or invalid json value (406).',
            429 => 'Sendinblue rate limit is exceeded. (429).',
            500 => 'Sendinblue internal server error (500).'
        ];
        $errorMessage = Craft::t('newsletter', 'The newsletter service is not available at that time. Please, try again later.');
        if (array_key_exists($apiException->getCode(), $errorLogMessages)) {
            Craft::error($errorLogMessages[$apiException->getCode()] . " " . VarDumper::dumpAsString($apiException), __METHOD__);
        } else {
            Craft::error("Sendinblue unknown error ({$apiException->getCode()}). " . VarDumper::dumpAsString($apiException->getResponseBody()), __METHOD__);
        }
        return $errorMessage;
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
