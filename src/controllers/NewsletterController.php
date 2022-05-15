<?php
/**
 * @link https://www.simplonprod.co
 * @copyright Copyright (c) 2020 Simplon.Prod
 */

namespace simplonprod\newsletter\controllers;

use Craft;
use craft\web\Controller;
use simplonprod\newsletter\models\NewsletterForm;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * NewsletterController class
 *
 * @author albanjubert
 **/
class NewsletterController extends Controller
{
    protected array|int|bool $allowAnonymous = true;

    /**
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionSubscribe(): ?Response
    {
        $this->requirePostRequest();

        // form validation
        $newsletterForm = new NewsletterForm();
        $newsletterForm->email = $this->request->post('email');
        $newsletterForm->consent = $this->request->post('consent');

        // Subscribe failed, send the form back
        if (!$newsletterForm->subscribe()) {
            if ($this->request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $newsletterForm->getErrors(),
                ]);
            }

            Craft::$app->getUrlManager()->setRouteParams([
                'variables' => [
                    'newsletterForm' => $newsletterForm,
                ],
            ]);

            return null;
        }

        if ($this->request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
            ]);
        }

        return $this->redirectToPostedUrl();
    }
}
