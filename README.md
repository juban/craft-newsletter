# Newsletter Plugin for Craft CMS

[![Stable Version](https://img.shields.io/packagist/v/jub/craft-newsletter?label=stable)]((https://packagist.org/packages/jub/craft-newsletter))
[![Total Downloads](https://img.shields.io/packagist/dt/jub/craft-newsletter)](https://packagist.org/packages/jub/craft-newsletter)
![Tests status](https://github.com/juban/craft-newsletter/actions/workflows/ci.yml/badge.svg?branch=master)

![](logo.png)

Newsletter for Craft CMS plugin makes it possible to let end users subscribe with various emailing services from a
frontend form.

It currently supports the following services:

* Mailchimp
* Mailjet
* Brevo (ex Sendinblue)

This plugin is GDPR compliant and requires the user to give its consent when subscribing to the newsletter.

> 💡 Similar to Craft Mailer adapters, you can even [create your own adapter](#how-to-create-an-adapter) to connect to
> unsupported services.

## Requirements

This plugin requires Craft CMS 4.0.0 or later and PHP 8.0.2 or later.

> In order to support automatic Google reCAPTCHA verification, you will need to install `jub/craft-google-recaptcha`
> plugin.

## Installation

1. Install with composer via `composer require jub/craft-newsletter` from your project directory.
2. Install the plugin in the Craft Control Panel under Settings → Plugins, or from the command line
   via `./craft install/plugin newsletter`.

## Configuration

### Control Panel

You can manage configuration setting through the Control Panel by going to Settings → Newsletter

* From the **Service type** dropdown, select the service type you wish to use to handle newsletter users subscription
* Provide the required API keys and parameters [as described below](#service-configuration).
* If Google reCAPTCHA plugin is installed and enabled, choose whether to verify the submission or not using the **Enable
  Google reCAPTCHA** light-switch.

> ⚠️ When enabled, the Google reCAPTCHA feature will **not** render the frontend widget for you. You have to
> add `{{ craft.googleRecaptcha.render() }}` somewhere in the form view.

#### Service configuration

##### Mailjet settings

* **API Key** (`apiKey`)
* **API Secret** (`apiSecret`)
* **List ID** (optional) (`listId`)

You can find these informations in your [Mailjet account informations](https://app.mailjet.com/account) in the REST API
keys section.

You can provide a [contact list ID](https://app.mailjet.com/contacts) in order to subscribe the enduser to a specific
one.

> If no list ID is provided, user will only be created as a contact.

##### Brevo (ex Sendinblue) settings

* **API Key** (`apiKey`)
* **List ID** (optional, required if DOI is on) (`listId`)
* **Activate Double Opt-in (DOI)** (optional) (`doi`)
* **Mail Template ID** (required if DOI is on) (`doiTemplateId`)
* **Redirection URL** (required if DOI is on) (`doiRedirectionUrl`)

You can find these informations in your [Brevo account](https://app.brevo.com/settings/keys/api).

You can provide a [contact list ID](https://app.brevo.com/contact/list-listing) in order to subscribe the enduser to a
specific one.

You can also enable Brevo Double Opt-in feature. You will need a Sendinblue template as
described [here](https://help.brevo.com/hc/en-us/articles/360019540880-Create-a-double-opt-in-DOI-confirmation-template-for-Sendinblue-form).

> If no list ID is provided, user will only be created as a contact.

##### Mailchimp settings

* **API Key** (`apiKey`): You can find that information from
  your [Mailchimp account](https://us4.admin.mailchimp.com/account/api/).
* **Server prefix** (`serverPrefix`): You can find that information by looking at the url when logged into your
  Mailchimp account. For instance, `https://us4.admin.mailchimp.com/account/api/` indicate the server prefix to use
  is `us4`
* **Audience ID** (`listId`): You can find that information
  in `Audience` > `All contacts` > `Settings` >  `Audience name and campaign defaults`

### Configuration file

You can create a `newsletter.php` file in the `config` folder of your project and provide the settings as follow (
example):

```php
return [
    "adapterType"         => \juban\newsletter\adapters\Mailjet::class,
    "adapterTypeSettings" => [
        'apiKey'    => '',
        'apiSecret' => '',
        'listId'    => ''
    ],
    "recaptchaEnabled"    => true
];
```

Depending on the service and its specific settings, adjust the `adapterType` to the according service adapter class name
and provide required parameters in the `adapterTypeSettings` associative array (
see [Service configuration](#service-configuration)).

> ⚠️ Any value provided in that file will override the settings from the Control Panel.

## Front-end form

You can use the following template as a starting point for your registration form:

```twig
{# @var craft \craft\web\twig\variables\CraftVariable #}
{# @var newsletterForm \juban\newsletter\models\NewsletterForm #}

{% if craft.app.plugins.plugin('newsletter') is not null %}
    {# If there were any validation errors, a `newsletterForm` variable will be passed to the template, which contains the posted values and validation errors. If that’s not set, we’ll default to a new newsletterForm. #}
    {% set newsletterForm = newsletterForm ?? create('juban\\newsletter\\models\\NewsletterForm') %}

    {# success notification #}
    {% if success is defined and success %}
        <div role="alert">
            <p>{{ 'Your newsletter subscription has been taken into account. Thank you.'|t }}</p>
        </div>
    {% endif %}
    
    <form action="" method="post" accept-charset="UTF-8">
        {{ csrfInput() }}
        
        {# Subscription process is handled by the newsletter plugin controller #}
        {{ actionInput('newsletter/newsletter/subscribe') }}
        
        {# User will be redirected to the redirect input url upon successful subscription #}
        {{ redirectInput('thank-you') }}
        
        <label for="newsletter-consent">
        	<input type="checkbox" value="check" name="consent" id="newsletter-consent" required {% if newsletterForm.hasErrors('consent') %}aria-invalid="true" aria-describedby="consent-error"{% endif %}>
            {{'I agree to receive your emails and confirm that I have read your privacy policy.'|t}}
        </label>
        {% if newsletterForm.hasErrors('consent') %}
            <div id="consent-error" role="alert" class="text-sm text-error font-bold">{{ newsletterForm.getFirstError('consent') }}</div>
        {% endif %}
        
        <label for="newsletter-email">{{ 'Votre email'|t }}<span aria-hidden="true">*</span></label>
        <input id="newsletter-email" required name="email" type="email" placeholder="j.dupont@gmail.com" value="{{ newsletterForm.email }}" {% if newsletterForm.hasErrors('email') %}aria-invalid="true" aria-describedby="email-error"{% endif %}>

        {# firstname additional field (optional) #}
        <label for="newsletter-firstname">{{ 'Firstname'|t }}</label>
        <input id="newsletter-firstname" name="additionalFields[firstname]" type="text"
           value="{{ newsletterForm.additionalFields.firstname ?? '' }}">

        {# lastname additional field (optional) #}
        <label for="newsletter-lastname">{{ 'Lastname'|t }}</label>
        <input id="newsletter-lastname" name="additionalFields[lastname]" type="text"
           value="{{ newsletterForm.additionalFields.lastname ?? '' }}">
           
        {% if newsletterForm.hasErrors('email') %}
            <div id="email-error" role="alert" class="text-sm text-error font-bold">{{ newsletterForm.getFirstError('email') }}</div>
        {% endif %}
        
        <button type="submit">{{ 'Subscribe'|t }}</button>

    </form>
{% endif %}
```

The `newsletter/newsletter/subscribe` action expects the following inputs submitted as `POST`:

* `email`: User email to subscribe
* `consent`: Any value indicating that the user gives its consent to receive the newsletter

Optionally, you can provide additional fields depending on the service support and configuration.
Each additional field should be provided in one or mode `additionalFields` inputs indexed by its field name.
For example, to provide a `firstname` field, input should be named `additionalFields[firstname]`.

> ⚠️ Don’t forget to add `{{ craft.googleRecaptcha.render() }}` in the form view if the Google reCAPTCHA verification is
> enabled.

## XHR / AJAX form

Alternatively, you can submit the newsletter form with Javascript.   
This gives you more freedom to provide visual effects to the user and prevents the page from reloading and scrolling to
the top of the page.  
The downside is that you need to write the ajax-request.  
Use the example below as a reference, note the required `application/json` header:

```javascript
var data = new FormData();
data.append("consent", consent);
data.append("email", email);

var xhr = new XMLHttpRequest();
xhr.withCredentials = true;

xhr.addEventListener("readystatechange", function () {
    if (this.readyState === 4) {
        console.log(this.responseText);
    }
});

xhr.open("POST", "https://{YOUR_DOMAIN}/actions/newsletter/newsletter/subscribe/");
xhr.setRequestHeader("Accept", "application/json");
xhr.send(data);
```

> ⚠️ If the Google reCAPTCHA verification is enabled, add the `data.append("g-recaptcha-response", "");` to the request
> as well!

## Custom validations

You can provide your own validations on frontend form submission in a project module or a plugin using
the `afterValidate` event on the `NewsletterForm` model:

```php
use yii\base\Event;
use juban\googlerecaptcha\GoogleRecaptcha;

Event::on(
	NewsletterForm::class,
	NewsletterForm::EVENT_AFTER_VALIDATE,
	static function (Event $event) {
	    $form = $event->sender;	    
	    $isSpam = // custom spam detection logic...
	    if($isSpam) {
	    	$this->addError('email', 'Spam detected!');
	    }
	}
);
```

## How to create an adapter

To add a new adapter for unsupported services:

Create an adapter class that extends the `juban\newsletter\adapters\BaseNewsletterAdapter` class.

Some small example:

```php
namespace juban\newsletter\adapters;

use Craft;

class MySuperNewsletterAdapter extends BaseNewsletterAdapter
{

	// Declare every attribute required by the service (ie API keys and secrets, etc...)
    public $apiKey;
    // Store any error occurring in the subscribe method here
    private $_errorMessage;
    
    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        // Support for setting defined in environment variables or aliases
        $behaviors = parent::behaviors();
        $behaviors['parser'] = [
            'class'      => EnvAttributeParserBehavior::class,
            'attributes' => [
                'apiKey'
            ],
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'My Service Name'; // Service name as shown in the adapter type dropdown
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        // Render the adapter settings templates
        // Adapt the path according to your module / plugin
        return Craft::$app->getView()->renderTemplate('newsletter/newsletterAdapters/MySuperNewsletterAdapter/settings', [
            'adapter' => $this
        ]);
	    }
	
	/**
	 * Try to subscribe the given email into the newsletter mailing list service
	 * @param string $email
	 * @return bool
	 */
    public function subscribe(string $email, array $additionalFields = null): bool
    {
        // Call the service API here
        $this->_errorMessage = null;
        if(!MySuperNewsletterService::subscribe($email, $additionalFields)) {
            // If something goes wrong, store the error message
            $this->_errorMessage = MySuperNewsletterService::getError();
            return false;
        }
        return true;
    }

    /**
     * Return the latest error message after a call to the subscribe method
     * @return null|string
     */
    public function getSubscriptionError(): string
    {
        return $this->_errorMessage;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        // Validation rules for the adapter settings should be defined here
        $rules = parent::defineRules();
        $rules[] = [['apiKey'], 'trim'];
        $rules[] = [['apiKey'], 'required'];
        return $rules;
    }
}
```

The template settings view could look like this:

```twig
{% import "_includes/forms" as forms %}

{{ forms.autosuggestField({
    label: "API Key"|t('newsletter'),
    id: 'apiKey',
    name: 'apiKey',
    required: true,
    suggestEnvVars: true,
    value: adapter.apiKey,
    errors: adapter.getErrors('apiKey')
}) }}
```

> The adapter model can be accessed in the twig view using the `adapter` variable.

Last, in a module or a plugin, register the adapter as follow:

```php
use craft\events\RegisterComponentTypesEvent;
use juban\newsletter\Newsletter;

Event::on(
    Newsletter::class,
    Newsletter::EVENT_REGISTER_NEWSLETTER_ADAPTER_TYPES,
    static function (RegisterComponentTypesEvent $event) {
        $event->types[] = MySuperNewsletterAdapter::class;
    }
);
```

## Roadmap

* Support for multiple lists

---

<small>Base plugin icon makes use of [Font Awesome Free](https://fontawesome.com)</small>
