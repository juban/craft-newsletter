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

    /**
     * @return \yii\web\Response|null
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionSubscribe(): ?\yii\web\Response
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

        return $this->redirectToPostedUrl();
    }

}
