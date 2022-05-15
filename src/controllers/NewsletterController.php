<?php
/**
 * @link https://www.simplonprod.co
 * @copyright Copyright (c) 2020 Simplon.Prod
 */

namespace simplonprod\newsletter\controllers;

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
            return $this->asModelFailure(
                $newsletterForm,
                null,
                'newsletterForm',
                data: [
                    'success' => false,
                ]
            );
        }

        // Subscribe was successful
        return $this->asModelSuccess(
            $newsletterForm,
            null,
            data: [
                'success' => true,
            ]
        );
    }
}
