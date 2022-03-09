<?php
/**
 * Newsletter plugin for Craft CMS 3.x
 *
 * Craft CMS Newsletter plugin
 *
 * @link      https://www.simplonprod.co
 * @copyright Copyright (c) 2021 Simplon.Prod
 */

/**
 * Newsletter config.php
 *
 * This file exists only as a template for the Newsletter settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'newsletter.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    "adapterType" => \simplonprod\newsletter\adapters\Mailjet::class,
    "adapterTypeSettings" => [
        'apiKey' => '',
        'apiSecret' => '',
        'listId' => '',
    ],
    "recaptchaEnabled" => true,
];
