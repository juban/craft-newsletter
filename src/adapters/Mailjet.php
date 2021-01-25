<?php
/**
 * @link https://www.simplonprod.co
 * @copyright Copyright (c) 2021 Simplon.Prod
 */

namespace simplonprod\newsletter\adapters;


use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use Mailjet\Client;
use Mailjet\Resources;

/**
 * Mailjet class
 *
 * @author albanjubert
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
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['parser'] = [
            'class'      => EnvAttributeParserBehavior::class,
            'attributes' => [
                'apiKey',
                'apiSecret',
                'listId'
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
            'apiKey'    => Craft::t('newsletter', 'API Key'),
            'apiSecret' => Craft::t('newsletter', 'API Secret'),
            'listId'    => Craft::t('newsletter', 'Contact List ID')
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('newsletter/newsletterAdapters/Mailjet/settings', [
            'adapter' => $this
        ]);
    }

    /**
     * @inheritdoc
     */
    public function subscribe(string $email): bool
    {
        $client = $this->_getClient();
        if (!$this->_contactExist($email, $client)) {
            if (!$this->_registerContact($email, $client)) {
                return false;
            }
        }
        if ($this->listId) {
            return $this->_subscribeContactToList($email, $client);
        }
        return true;
    }

    /**
     * @return Client
     */
    private function _getClient(): Client
    {
        if (is_null($this->_client)) {
            $this->_client = new Client(Craft::parseEnv($this->apiKey), Craft::parseEnv($this->apiSecret), true, ['version' => 'v3']);
        }
        return $this->_client;
    }

    /**
     * @param string $email
     * @param Client $client
     * @return bool
     */
    private function _contactExist(string $email, Client $client): bool
    {
        // Check if contact already exists
        $response = $client->get(Resources::$Contact, ['id' => $email]);
        return $response->success();
    }

    /**
     * @param string $email
     * @param Client $client
     * @return bool
     */
    private function _registerContact(string $email, Client $client): bool
    {
        $body = [
            'IsExcludedFromCampaigns' => "false",
            'Email'                   => $email
        ];
        $this->_errorMessage = null;

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
        $messages = [
            'mj18' => Craft::t('newsletter', "You are already subscribed to our newsletter."),
            'mj08' => Craft::t('newsletter', "Please provide a valid email address."),
        ];

        $body = $response->getBody();
        $output = Craft::t('newsletter', "Unknown error. Please, try again.");
        foreach ($messages as $errorCode => $errorMessage) {
            if (preg_match("/{$errorCode}/i", implode(" ", [($body['ErrorInfo'] ?? ""), ($body['ErrorMessage'] ?? "")]))) {
                $output = $errorMessage;
            }
        }
        return $output;
    }

    /**
     * @param string $email
     * @param Client $client
     * @return bool
     */
    private function _subscribeContactToList(string $email, Client $client): bool
    {
        // Register contact to list
        $body = [
            'Properties' => "object",
            'Action'     => "addnoforce",
            'Email'      => $email
        ];
        $response = $client->post(Resources::$ContactslistManagecontact, ['id' => $this->listId, 'body' => $body]);
        if (!$response->success()) {
            $this->_errorMessage = $this->_getErrorMessageFromRessource($response);
        }
        return $response->success();
    }

    /**
     * @inheritdoc
     */
    public function getSubscriptionError()
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
