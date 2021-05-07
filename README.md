# Newsletter Plugin for Craft CMS

![](logo.png)

Newsletter for Craft CMS plugin enables users subscription with various emailing services.

It is actually compatible out of the box with:

* Mailchimp
* Mailjet
* Sendinblue

This plugin is GDPR compliant and require the user gives its consent when subscribing to the newsletter.

> Similar to Craft Mailer adapters, you can even create your own adapter to connect to unsupported services.

## Requirements

This plugin requires Craft CMS 3.5.0 or later and PHP 7.2.5 or later.

> In order to support automatic Google reCAPTCHA verification, you will need to install `simplonprod/craft-google-recaptcha` plugin.


## Installation

1. Install with composer via `composer require simplonprod/craftcms-newsletter` from your project directory.
2. Install the plugin in the Craft Control Panel under Settings → Plugins, or from the command line via `./craft install/plugin newsletter`.

## Configuration

### Control Panel

You can manage configuration setting through the Control Panel by going to Settings → Newsletter

* From the Service type dropdown, select the service type you wish to use to handle newsletter users subscription
* Provide the required API keys and parameters [as described below](#Service-configuration).  
* If Google reCAPTCHA plugin is installed and enabled, choose whether to verify the submission or not using the Enable Google reCAPTCHA light-switch.

### Service configuration

#### Mailjet

Provide an API Key and API Secret from your [Mailjet account informations](https://app.mailjet.com/account) in the REST API keys section.

You can also provide a [contact list ID](https://app.mailjet.com/contacts) in order to subscribe the enduser to a specific one. 
> If no list ID is provided, user will only be created as a contact.

#### Sendinblue

Provide an API Key from your [Sendinblue account](https://account.sendinblue.com/advanced/api).

You can also provide a [contact list ID](https://my.sendinblue.com/lists) in order to subscribe the enduser to a specific one. 
> If no list ID is provided, user will only be created as a contact.

#### Mailchimp

Provide :

* API Key from your [Mailchimp account](https://us4.admin.mailchimp.com/account/api/).
* Server prefix which you can get by looking at the url when logged into your Mailchimp account.  
For instance, `https://us4.admin.mailchimp.com/account/api/` indicate the server prefix to use is `us4`
* Audience ID which you will find in `Audience` > `All contacts` > `Settings` >  `Audience name and campaign defaults`


## Front-end forms

You can use the following template as a starting point for your registration form:

```twig
{# @var craft \craft\web\twig\variables\CraftVariable #}
{# @var newsletterForm \simplonprod\newsletter\models\NewsletterForm #}

{% if craft.app.plugins.plugin('newsletter') is not null %}
    {# If there were any validation errors, a `newsletterForm` variable will be passed to the template, which contains the posted values and validation errors. If that’s not set, we’ll default to a new newsletterForm. #}
    {% set newsletterForm = newsletterForm ?? create('simplonprod\\newsletter\\models\\NewsletterForm') %}

    {# success notification #}
    {% if success is defined and success %}
        <div role="alert">
            <p>{{ 'Your newsletter subscription has been taken into account. Thank you.'|t }}</p>
        </div>
    {% endif %}
    
    <form action="" method="post">
        {{ csrfInput() }}
        {# subscription process is handled by the newsletter plugin controller #}
        {{ actionInput('newsletter/newsletter/subscribe') }}

        <label for="newsletter-consent">
        	<input type="checkbox" value="check" name="consent" id="newsletter-consent" required {% if newsletterForm.hasErrors('consent') %}aria-invalid="true" aria-describedby="consent-error"{% endif %}>
            {{'I agree to receive your emails and confirm that I have read your privacy policy.'|t}}
        </label>
        {% if newsletterForm.hasErrors('consent') %}
            <div id="consent-error" role="alert" class="text-sm text-error font-bold">{{ newsletterForm.getFirstError('consent') }}</div>
        {% endif %}
        
        <label for="newsletter-email">{{ 'Votre email'|t }}<span aria-hidden="true">*</span></label>
        <input id="newsletter-email" required name="email" type="email" placeholder="j.dupont@gmail.com" value="{{ newsletterForm.email }}" {% if newsletterForm.hasErrors('email') %}aria-invalid="true" aria-describedby="email-error"{% endif %}>

        {% if newsletterForm.hasErrors('email') %}
            <div id="email-error" role="alert" class="text-sm text-error font-bold">{{ newsletterForm.getFirstError('email') }}</div>
        {% endif %}
        
        <button type="submit">{{ 'Subscribe'|t }}</button>

    </form>
{% endif %}
```

The `newsletter/newsletter/subscribe` action expect the following inputs submitted as `POST`:

* `email`: User email to subscribe
* `consent`: Any value indicating that the user gives its consent to receive the newsletter

## Custom validations

You can provide your own validations on frontend form submission in a project module or a plugin using the `afterValidate` event on the `NewsletterForm` model:

```php
use yii\base\Event;
use simplonprod\googlerecaptcha\GoogleRecaptcha;

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

---

<small>Created by [Simplon.Prod](https://www.simplonprod.co/).</small>
