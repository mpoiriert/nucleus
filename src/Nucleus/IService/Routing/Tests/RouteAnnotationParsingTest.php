<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Routing\Tests;

/**
 * Description of RouteParsingTest
 *
 * @author Martin
 */

abstract class RouteAnnotationParsingTest extends \PHPUnit_Framework_TestCase
{
     /**
     *
     * @var Nucleus\Routing\Router
     */
    private $routingService;

    /**
     * 
     * @param $serviceName The name of the service
     * @param $class A class name that need to be register as service and being parse
     * 
     * @return Nucleus\IService\Routing\Router
     */
    abstract protected function getRoutingService($serviceName, $class);

    /**
     * @return \Nucleus\Routing\Router
     */
    protected function loadRoutingService()
    {
        if (is_null($this->routingService)) {
            $this->routingService = $this->getRoutingService('serviceForTest', 'Nucleus\IService\Routing\Tests\TestableService');
            $this->assertInstanceOf('Nucleus\IService\Routing\IRouterService', $this->routingService);
        }

        return $this->routingService;
    }
    
    public function testRouteConnection()
    {
        $serviceRouter = $this->loadRoutingService();
        $result = $serviceRouter->match('/test');
        $this->assertEquals(
            array(
            'test' => 0,
            '_service' => array('name' => 'serviceForTest', 'method' => 'route'),
            '_route' => 'test'
            ), $result
        );

        $result = $serviceRouter->match('/test-en-us');
        unset($result['_route']);
        $this->assertEquals(
            array(
            'test' => 0,
            '_service' => array('name' => 'serviceForTest', 'method' => 'route'),
            '_culture' => 'en-us'
            ), $result
        );
        
        
        $result = $serviceRouter->generate('test-i18n');
        $this->assertEquals('/test-i18n', $result);
        
        $serviceRouter->setDefaultCulture('en-us');
        $result = $serviceRouter->generate('test-i18n');
        $this->assertEquals('/test-en-us', $result);
    }
}
