<?php


namespace simplonprod\newsletter\adapters;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use MailchimpMarketing\Api\ListsApi;
use yii\helpers\Json;
use yii\helpers\VarDumper;
use MailchimpMarketing\ApiClient;

class Mailchimp extends BaseNewsletterAdapter
{
    public $apiKey;
    public $serverPrefix;
    public $listId;

    private $_errorMessage;
    private $_client;
    private $_listApi;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Mailchimp';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['parser'] = [
            'class' => EnvAttributeParserBehavior::class,
            'attributes' => [
                'apiKey',
                'serverPrefix',
                'listId'
            ],
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('newsletter/newsletterAdapters/Mailchimp/settings', [
            'adapter' => $this
        ]);
    }

    public function getClient(): ApiClient
    {
        if (is_null($this->_client)) {
            $mailchimp = new ApiClient();

            $mailchimp->setConfig([
                'apiKey' => Craft::parseEnv($this->apiKey),
                'server' => Craft::parseEnv($this->serverPrefix),
            ]);

            $this->_client = $mailchimp;
        }

        return $this->_client;
    }

    public function getListApi(ApiClient $client): ListsApi
    {
        if (is_null($this->_listApi)) {
            $this->_listApi = new ListsApi($client);
        }
        return $this->_listApi;
    }

    public function setListApi(ListsApi $listsApi): void
    {
        $this->_listApi = $listsApi;
    }

    public function subscribe(string $email): bool
    {
        $client = $this->getClient();
        $listsApi = $this->getListApi($client);
        $parsedListId = Craft::parseEnv($this->listId);

        if (!$this->_contactExist($email, $listsApi, $parsedListId)) {
            return $this->_registerContact($email, $listsApi, $parsedListId);
        }

        return true;
    }

    private function _contactExist(string $email, ListsApi $listsApi, string $listId): bool
    {
        try {
            $listsApi->getListMember($listId, md5($email));
            return true;
        } catch (ClientException $clientException) {
            $this->_getErrorMessage($clientException);
            return false;
        } catch (ConnectException $connectionException) {
            $this->_getErrorConnect($connectionException);
            return false;
        }
    }

    private function _registerContact(string $email, ListsApi $listsApi, string $listId): bool
    {
        try {
            $listsApi->addListMember($listId, [
                "email_address" => $email,
                "status" => "subscribed",
            ]);

            return true;
        } catch (ClientException $clientException) {
            $this->_errorMessage = $this->_getErrorMessage($clientException);
            return false;
        } catch (ConnectException $connectException) {
            $this->_errorMessage = $this->_getErrorConnect($connectException);
            return false;
        }
    }

    private function _getErrorConnect(ConnectException $connectException): string
    {
        $errorMessage = Craft::t('newsletter', 'The newsletter service is not available at that time. Please, try again later.');
        Craft::error('Mailchimp : ' . VarDumper::dumpAsString($connectException->getMessage()), __METHOD__);

        return $errorMessage;
    }

    private function _getErrorMessage(ClientException $clientException): string
    {
        $errorLogMessages = [
            400 => 'Mailchimp Request is invalid. Check the error code in JSON (400).',
            401 => 'Mailchimp Your API key may be invalid, or you’ve attempted to access the wrong data center (401).',
            403 => 'Mailchimp You are not permitted to access this resource (403).',
            404 => 'Mailchimp the requested resource could not be found (404).',
            405 => 'Mailchimp The requested method and resource are not compatible. See the Allow header for this resource’s available methods (405).',
            414 => 'Mailchimp The sub-resource requested is nested too deeply (414).',
            422 => 'Mailchimp You can only use the X-HTTP-Method-Override header with the POST method (422).',
            429 => 'Mailchimp You have exceeded the limit of 10 simultaneous connections (429).',
            500 => 'Mailchimp An unexpected internal error has occurred. Please contact Support for more information (500).',
            503 => 'Mailchimp This method has been disabled (503).',
        ];
        $errorMessage = Craft::t('newsletter', 'The newsletter service is not available at that time. Please, try again later.');
        if (array_key_exists($clientException->getCode(), $errorLogMessages)) {
            Craft::error($errorLogMessages[$clientException->getCode()] . " " . VarDumper::dumpAsString($clientException->getResponse()), __METHOD__);
        } else {
            $body = Json::decode($clientException->getResponse()->getBody()->getContents(), false);
            $errorMessage = Craft::t('newsletter', 'An error has occurred : {errorMessage}.', ['errorMessage' => $body->detail ?? '']);
        }
        return $errorMessage;
    }

    public function setApiClient(ApiClient $client): void
    {
        $this->_client = $client;
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
        $rules[] = [['listId'], 'trim'];
        $rules[] = [['serverPrefix'], 'trim'];
        $rules[] = [['apiKey'], 'required'];
        $rules[] = [['listId'], 'required'];
        $rules[] = [['serverPrefix'], 'required'];

        return $rules;
    }
}
