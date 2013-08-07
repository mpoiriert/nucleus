<?php

namespace Nucleus\Routing\Tests;

use Nucleus\Routing\Router;
use Nucleus\IService\Routing\Tests\RouterServiceTest;

class RouterTest extends RouterServiceTest
{
    /**
     * 
     * @return Nucleus\Routing\Router
     */
    protected function getRoutingService()
    {
        return Router::factory();
    }
}