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
        $this->_errorMessage = null;
        $clientContactApi = $this->getClientContactApi();
        $parsedListId = Craft::parseEnv($this->listId);

        if (!$this->_contactExist($email, $clientContactApi)) {

            if (!empty($this->listId) && (int)$parsedListId !== 0) {
                return $this->_registerContactToList($email, $clientContactApi, (int)$parsedListId);
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
            $body = Json::decode($apiException->getResponseBody(), false);
            $errorMessage = Craft::t('newsletter', 'An error has occurred : {errorMessage}.', ['errorMessage' => $body->message ?? '']);
        }
        return $errorMessage;
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
