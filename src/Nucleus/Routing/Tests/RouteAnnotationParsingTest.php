<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Routing\Tests;

use Nucleus\Routing\Router;

/**
 * @author Martin
 */
class RouteAnnotationParsingTest extends \Nucleus\IService\Routing\Tests\RouteAnnotationParsingTest
{
    /**
     * 
     * @param $serviceName The name of the service
     * @param $class A class name that need to be register as service and being parse
     * 
     * @return Nucleus\IService\Routing\Router
     */
    protected function getRoutingService($serviceName, $class)
    {
        return Router::factory(
            array(
              'imports' => array(__DIR__ . '/../'),
              'services' => array(
                  $serviceName => array(
                      'class' => $class
                  )
              )
            )
        );
    }
}
