<?php


namespace juban\newsletter\adapters;

use Brevo\Client\Api\ContactsApi;
use Brevo\Client\ApiException;
use Brevo\Client\Configuration;
use Brevo\Client\Model\CreateContact;
use Brevo\Client\Model\CreateDoiContact;
use Brevo\Client\Model\UpdateContact;
use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\helpers\App;
use GuzzleHttp\Client;
use yii\helpers\VarDumper;

/**
 *
 * @property-read ContactsApi $clientContactApi
 * @property-read mixed $settingsHtml
 * @property-read null|string $subscriptionError
 */
class Brevo extends BaseNewsletterAdapter
{
    public $apiKey;
    public $listId;
    public $doi = false;
    public $doiTemplateId;
    public $doiRedirectionUrl;

    private $_errorMessage;
    private $_contactsApi;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Brevo (ex. Sendinblue)';
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
                'listId',
                'doi',
                'doiTemplateId',
                'doiRedirectionUrl',
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
            'doi' => Craft::t('newsletter', 'Activate Double Opt-in'),
            'doiTemplateId' => Craft::t('newsletter', 'Double Opt-in Template ID'),
            'doiRedirectionUrl' => Craft::t('newsletter', 'Double Opt-in Redirection URL'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('newsletter/newsletterAdapters/Brevo/settings', [
            'adapter' => $this,
        ]);
    }

    public function subscribe(string $email, array $additionalFields = null): bool
    {
        $clientContactApi = $this->getClientContactApi();
        $listId = (int)App::parseEnv($this->listId);

        if (!$this->_contactExist($email, $clientContactApi)) {
            if ($listId !== 0) {
                return $this->_registerContactToList($email, $listId, $clientContactApi, $additionalFields);
            }

            return $this->_registerContact($email, $clientContactApi, $additionalFields);
        }

        if ($listId !== 0) {
            return $this->_addContactToList($email, $listId, $clientContactApi, $additionalFields);
        }

        return true;
    }

    public function getClientContactApi(): ContactsApi
    {
        if (!$this->_contactsApi) {
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', App::parseEnv($this->apiKey));

            $this->_contactsApi = new ContactsApi(
                new Client(),
                $config
            );
        }

        return $this->_contactsApi;
    }

    public function setClientContactApi(ContactsApi $contactsApi): void
    {
        $this->_contactsApi = $contactsApi;
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

    private function _registerContactToList(
        string $email,
        int $listId,
        ContactsApi $clientContactApi,
        array $attributes = null
    ): bool
    {
        try {
            if (App::parseBooleanEnv($this->doi)) {
                $contact = new CreateDoiContact([
                    'email' => $email,
                    'attributes' => $attributes,
                    'includeListIds' => [$listId],
                    'templateId' => (int)App::parseEnv($this->doiTemplateId),
                    'redirectionUrl' => App::parseEnv($this->doiRedirectionUrl),
                ]);
                $clientContactApi->createDoiContact($contact);
            } else {
                $contact = new CreateContact([
                    'email' => $email,
                    'attributes' => $attributes,
                    'listIds' => [$listId],
                ]);
                $clientContactApi->createContact($contact);
            }
            return true;
        } catch (ApiException $apiException) {
            $this->_errorMessage = $this->_getErrorMessage($apiException);
            return false;
        }
    }

    private function _registerContact(string $email, ContactsApi $clientContactApi, array $attributes = null): bool
    {
        try {
            $contact = new CreateContact(['email' => $email, 'attributes' => $attributes]);
            $clientContactApi->createContact($contact);
            return true;
        } catch (ApiException $apiException) {
            $this->_errorMessage = $this->_getErrorMessage($apiException);
            return false;
        }
    }

    private function _addContactToList(string $email, int $listId, ContactsApi $clientContactApi, array $attributes = null): bool
    {
        try {
            $contact = new UpdateContact(['listIds' => [$listId], 'attributes' => $attributes]);
            $clientContactApi->updateContact($email, $contact);
            return true;
        } catch (ApiException $apiException) {
            $this->_errorMessage = $this->_getErrorMessage($apiException);
            return false;
        }
    }

    private function _getErrorMessage(ApiException $apiException): string
    {
        $errorLogMessages = [
            400 => 'Brevo request is invalid. Check the error code in JSON (400).',
            401 => 'Brevo authentication error (401). Make sure the provided api-key is correct.',
            403 => 'Brevo resource access error (403).',
            404 => 'Brevo resource was not found (404).',
            405 => 'Brevo verb is not allowed for this endpoint (405).',
            406 => 'Brevo empty or invalid json value (406).',
            429 => 'Brevo rate limit is exceeded. (429).',
            500 => 'Brevo internal server error (500).',
        ];
        $errorMessage = Craft::t('newsletter', 'The newsletter service is not available at that time. Please, try again later.');
        if (array_key_exists($apiException->getCode(), $errorLogMessages)) {
            Craft::error($errorLogMessages[$apiException->getCode()] . " " . VarDumper::dumpAsString($apiException), __METHOD__);
        } else {
            Craft::error("Brevo unknown error ({$apiException->getCode()}). " . VarDumper::dumpAsString($apiException->getResponseBody()), __METHOD__);
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
        $rules[] = [['listId', 'doiTemplateId'], 'integer'];
        $rules[] = [['doiRedirectionUrl'], 'string'];
        if ($this->doi) {
            $rules[] = [['listId', 'doiTemplateId', 'doiRedirectionUrl'], 'required'];
        }
        return $rules;
    }
}
