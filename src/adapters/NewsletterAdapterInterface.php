<?php

namespace juban\newsletter\adapters;

use craft\base\ConfigurableComponentInterface;

interface NewsletterAdapterInterface extends ConfigurableComponentInterface
{
    /**
     * Try to subscribe the given email into the newsletter mailing list service
     * @param string $email
     * @param array|null $additionalFields
     * @return bool
     */
    public function subscribe(string $email, array $additionalFields = null);

    /**
     * Return the latest error message after a call to the subscribe method
     * @return null|string
     */
    public function getSubscriptionError();
}
