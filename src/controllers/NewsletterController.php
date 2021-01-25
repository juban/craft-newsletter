<?php
/**
 * @link https://www.simplonprod.co
 * @copyright Copyright (c) 2020 Simplon.Prod
 */

namespace simplonprod\newsletter\controllers;

use Craft;
use craft\web\Controller;
use simplonprod\newsletter\models\NewsletterForm;

/**
 * NewsletterController class
 *
 * @author albanjubert
 **/
class NewsletterController extends Controller
{
    protected $allowAnonymous = true;

    public function actionSubscribe()
    {
        $this->requirePostRequest();

        // form validation
        $newsletterForm = new NewsletterForm();
        $newsletterForm->email = $this->request->post('email');
        $newsletterForm->consent = $this->request->post('consent');

        if (!$newsletterForm->subscribe()) {
            // Send the form back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'newsletterForm' => $newsletterForm
            ]);
            return null;
        }

        Craft::$app->session->setNotice(Craft::t('newsletter', "Your newsletter subscription has been taken into account. Thank you."));
        return $this->redirectToPostedUrl();
    }

}
