<?php
/**
 * @link https://www.simplonprod.co
 * @copyright Copyright (c) 2021 Simplon.Prod
 */

namespace simplonprod\newslettertests\unit;

use Codeception\Test\Unit;
use simplonprod\newsletter\Newsletter;
use yii\base\InvalidRouteException;

/**
 * BaseUnitTest class
 *
 * @author albanjubert
 **/
class BaseUnitTest extends Unit
{
    /**
     * @param string $action
     * @param array $params
     *
     * @return mixed
     * @throws InvalidRouteException
     */
    protected function runActionWithParams(string $action, array $params)
    {
        \Craft::$app->request->setBodyParams($params);

        return Newsletter::$plugin->runAction($action);
    }
}
