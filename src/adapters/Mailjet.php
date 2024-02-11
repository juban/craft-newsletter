<?php

namespace juban\newsletter\adapters;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\helpers\App;
use Mailjet\Client;
use Mailjet\Resources;
use Mailjet\Response;
use yii\helpers\VarDumper;

/**
 * Mailjet class
 *
 * @author juban
 **/
class Mailjet extends BaseNewsletterAdapter
{
    public $apiKey;

    public $apiSecret;

    public $listId;

    private $_client;

    private $_errorMessage;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Mailjet';
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
                'apiSecret',
                'listId',
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
            'apiSecret' => Craft::t('newsletter', 'API Secret'),
            'listId' => Craft::t('newsletter', 'Contact List ID'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('newsletter/newsletterAdapters/Mailjet/settings', [
            'adapter' => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function subscribe(string $email, array $additionalFields = null): bool
    {
        $this->_errorMessage = null;
        $client = $this->getClient();
        $contactId = $this->_getContactId($client, $email);
        if (!$contactId) {
            $contactId = $this->_registerContact($client, $email);
            if (!$contactId) {
                return false;
            }
        }

        if (null !== $additionalFields && !$this->_updateContactData($client, $contactId, $additionalFields)) {
            return false;
        }

        if ($this->listId) {
            return $this->_subscribeContactToList($client, $email);
        }

        return true;
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        if (is_null($this->_client)) {
            $this->_client = new Client(
                App::parseEnv($this->apiKey),
                App::parseEnv($this->apiSecret),
                true,
                ['version' => 'v3']
            );
        }

        return $this->_client;
    }

    /**
     * @return int|null
     */
    private function _getContactId(Client $client, string $email): ?int
    {
        $response = $client->get(Resources::$Contact, ['id' => $email]);
        if ($response->success()) {
            return $response->getData()[0]['ID'] ?? null;
        }

        return null;
    }

    /**
     * @return int|null
     */
    private function _registerContact(Client $client, string $email): ?int
    {
        $body = [
            'IsExcludedFromCampaigns' => "false",
            'Email' => $email,
        ];

        $response = $client->post(Resources::$Contact, ['body' => $body]);
        if (!$response->success()) {
            $this->_errorMessage = $this->_getErrorMessageFromRessource($response);
            return null;
        }

        return $response->getData()[0]['ID'] ?? null;
    }

    /**
     * @return string
     */
    private function _getErrorMessageFromRessource(Response $response): string
    {
        $errorLogMessages = [
            400 => 'Mailjet bad request occurred (400).',
            401 => 'Mailjet apiKey or secretKey are incorrect (401).',
            403 => 'Mailjet did not authorized to access a resource (403).',
            404 => 'Mailjet resource was not found (404).',
            405 => 'Mailjet method requested on the resource does not exist (405).',
            429 => 'Mailjet maximum number of calls allowed per minute was reached (429).',
            500 => 'Mailjet internal server error (500).',
        ];
        $errorMessage = Craft::t(
            'newsletter',
            'The newsletter service is not available at that time. Please, try again later.'
        );
        if (array_key_exists($response->getStatus(), $errorLogMessages)) {
            Craft::error(
                $errorLogMessages[$response->getStatus()] . " " . VarDumper::dumpAsString($response),
                __METHOD__
            );
        } else {
            $body = $response->getBody();
            if (isset($body['ErrorInfo']) || isset($body['ErrorMessage'])) {
                Craft::error(
                    sprintf('Mailjet unknown error (%s). ', $response->getStatus()) . VarDumper::dumpAsString($response),
                    __METHOD__
                );
            }
        }

        return $errorMessage;
    }

    /**
     * @return bool
     */
    private function _updateContactData(Client $client, int $contactId, array $data): bool
    {
        $body = array_map(static fn($key, $value) => ['Name' => $key, 'Value' => $value],
            array_keys($data),
            array_values($data));
        $response = $client->put(Resources::$Contactdata, ['id' => $contactId, 'body' => ['Data' => $body]]);
        if (!$response->success()) {
            $this->_errorMessage = $this->_getErrorMessageFromRessource($response);
        }

        return $response->success();
    }

    /**
     * @return bool
     */
    private function _subscribeContactToList(Client $client, string $email): bool
    {
        // Register contact to list
        $body = [
            'Properties' => "object",
            'Action' => "addforce",
            'Email' => $email,
        ];
        $response = $client->post(
            Resources::$ContactslistManagecontact,
            ['id' => App::parseEnv($this->listId), 'body' => $body]
        );
        if (!$response->success()) {
            $this->_errorMessage = $this->_getErrorMessageFromRessource($response);
        }

        return $response->success();
    }

    /**
     * @inheritdoc
     */
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
        $rules[] = [['apiKey', 'apiSecret'], 'trim'];
        $rules[] = ['listId', 'integer'];
        $rules[] = [['apiKey', 'apiSecret'], 'required'];
        $rules[] = [['apiKey', 'apiSecret'], 'match', 'pattern' => '/^\w*$/i'];
        return $rules;
    }
}
