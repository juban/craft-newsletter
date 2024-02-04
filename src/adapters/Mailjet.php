<?php

namespace juban\newsletter\adapters;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\helpers\App;
use Mailjet\Client;
use Mailjet\Resources;
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
        if (!$this->_contactExist($client, $email) && !$this->_registerContact($client, $email)) {
            return false;
        }
        if (null !== $additionalFields && !$this->_updateContactData($client, $email, $additionalFields)) {
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
     * @param Client $client
     * @param string $email
     * @return bool
     */
    private function _contactExist(Client $client, string $email): bool
    {
        // Check if contact already exists
        return $client->get(Resources::$Contact, ['id' => $email])->success();
    }

    /**
     * @param Client $client
     * @param string $email
     * @return bool
     */
    private function _registerContact(Client $client, string $email): bool
    {
        $body = [
            'IsExcludedFromCampaigns' => "false",
            'Email' => $email,
        ];

        $response = $client->post(Resources::$Contact, ['body' => $body]);
        if (!$response->success()) {
            $this->_errorMessage = $this->_getErrorMessageFromRessource($response);
        }
        return $response->success();
    }

    /**
     * @param \Mailjet\Response $response
     * @return string
     */
    private function _getErrorMessageFromRessource(\Mailjet\Response $response): string
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
                    "Mailjet unknown error ({$response->getStatus()}). " . VarDumper::dumpAsString($response),
                    __METHOD__
                );
            }
        }
        return $errorMessage;
    }

    /**
     * @param Client $client
     * @param string $email
     * @param array $data
     * @return bool
     */
    private function _updateContactData(Client $client, string $email, array $data): bool
    {
        $body = array_map(static fn($key, $value) => ['Name' => $key, 'Value' => $value],
            array_keys($data),
            array_values($data));
        $response = $client->post(Resources::$Contactdata, ['contact_email' => $email, 'body' => $body]);
        if (!$response->success()) {
            $this->_errorMessage = $this->_getErrorMessageFromRessource($response);
        }
        return $response->success();
    }

    /**
     * @param Client $client
     * @param string $email
     * @return bool
     */
    private function _subscribeContactToList(Client $client, string $email): bool
    {
        // Register contact to list
        $body = [
            'Properties' => "object",
            'Action' => "addnoforce",
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
