<?php

namespace Nucleus\Routing\Tests;

use Nucleus\Routing\Router;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var Nucleus\Routing\Router
     */
    private $routingService;

    /**
     * 
     * @return Nucleus\Routing\Router
     */
    protected function getRoutingService()
    {
        return Router::factory();
    }

    /**
     * @return \Nucleus\Routing\Router
     */
    protected function loadRoutingService()
    {
        if (is_null($this->routingService)) {
            $this->routingService = $this->getRoutingService();
        }

        return $this->routingService;
    }

    public function providerMatchRoute()
    {
        return array(
            array('/test', array('default' => 0), 'test', '/test', array('default' => 0)),
            array('/test', array('default' => 0, 'test' => 'test'), 'test', '/{test}', array('default' => 0)),
            array('/en/test', array('lang' => 'en'), 'test', '/{lang}/test', array(), array('lang' => 'en'))
        );
    }

    /**
     * @dataProvider providerMatchRoute
     */
    public function testRoute($pathinfo, $expected, $name, $path, array $defaults = array(), array $requirements = array(), array $options = array(), $host = '', $schemes = array(), $methods = array())
    {
        $expected['_route'] = $name;
        $routing = $this->loadRoutingService();
        $routing->addRoute($name, $path, $defaults, $requirements, $options, $host, $schemes, $methods);
        $result = $routing->match($pathinfo);
        $this->assertEquals($expected, $result);
        $routing->removeRoute($name);
    }
}