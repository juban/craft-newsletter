<?php

namespace juban\newslettertests\unit;

use Codeception\Test\Unit;
use juban\newsletter\Newsletter;
use yii\base\InvalidRouteException;

/**
 * BaseUnitTest class
 *
 * @author juban
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
