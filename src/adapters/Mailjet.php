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
use yii\helpers\VarDumper;

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
        $errorMessage = Craft::t('newsletter', 'The newsletter service is not available at that time. Please, try again later.');
        if ($response->getStatus() === 401) {
            Craft::error('Mailjet apiKey or secretKey are incorrect (401). ' . VarDumper::dumpAsString($response), __METHOD__);
        } elseif ($response->getStatus() === 400) {
            Craft::error('Mailjet bad request occurred (400).' . VarDumper::dumpAsString($response), __METHOD__);
        } elseif ($response->getStatus() === 403) {
            Craft::error('Mailjet did not authorized to access a resource (403).' . VarDumper::dumpAsString($response), __METHOD__);
        } elseif ($response->getStatus() === 404) {
            Craft::error('Mailjet resource was not found (404).' . VarDumper::dumpAsString($response), __METHOD__);
        } elseif ($response->getStatus() === 405) {
            Craft::error('Mailjet method requested on the resource does not exist (405).' . VarDumper::dumpAsString($response), __METHOD__);
        } elseif ($response->getStatus() === 429) {
            Craft::error('Mailjet maximum number of calls allowed per minute was reached (429).' . VarDumper::dumpAsString($response), __METHOD__);
        } elseif ($response->getStatus() === 500) {
            Craft::error('Mailjet internal server error (500).' . VarDumper::dumpAsString($response), __METHOD__);
        } else {
            $body = $response->getBody();
            if (isset($body['ErrorInfo']) || isset($body['ErrorMessage'])) {
                $errorMessage = Craft::t('newsletter', "An error has occurred : {errorMessage}.", ['errorMessage' => trim(($body['ErrorInfo'] ?? "") . " " . ($body['ErrorMessage'] ?? ""))]);
                Craft::error(VarDumper::dumpAsString($response), __METHOD__);
            }
        }
        return $errorMessage;
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
        $response = $client->post(Resources::$ContactslistManagecontact, ['id' => Craft::parseEnv($this->listId), 'body' => $body]);
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
