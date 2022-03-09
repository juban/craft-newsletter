<?php
/**
 * @link https://www.simplonprod.co
 * @copyright Copyright (c) 2021 Simplon.Prod
 */

namespace simplonprod\newsletter\adapters;

use craft\base\ConfigurableComponentInterface;

interface NewsletterAdapterInterface extends ConfigurableComponentInterface
{
    /**
     * Try to subscribe the given email into the newsletter mailing list service
     * @param string $email
     * @return bool
     */
    public function subscribe(string $email);

    /**
     * Return the latest error message after a call to the subscribe method
     * @return null|string
     */
    public function getSubscriptionError();
}
