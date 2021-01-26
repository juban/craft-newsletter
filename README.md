# Newsletter Plugin for Craft CMS

Newsletter for Craft CMS is a plugin that enables users subscription with various services.

## Requirements

This plugin requires Craft CMS 3.5.0 or later.


## Installation

1. Install with composer via `composer require simplonprod/craftcms-newsletter` from your project directory.
2. Install the plugin in the Craft Control Panel under Settings → Plugins, or from the command line via `./craft install/plugin newsletter`.
3. Select and configure the service under Settings → Newsletter


## Front-end forms

You can use the following template as a starting point for your registration form:

```twig
{# @var craft \craft\web\twig\variables\CraftVariable #}
{# @var newsletterForm \simplonprod\newsletter\models\NewsletterForm #}

{% if craft.app.plugins.plugin('newsletter') is not null %}
	{# If there were any validation errors, a `newsletterForm` variable will be passed to the template, which contains the posted values and validation errors. If that’s not set, we’ll default to a new newsletterForm. #}
    {% set newsletterForm = newsletterForm ?? create('simplonprod\\newsletter\\models\\NewsletterForm') %}

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


## Supported services

* Mailjet


