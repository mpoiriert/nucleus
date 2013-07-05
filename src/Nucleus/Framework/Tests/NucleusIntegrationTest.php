<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Framework\Tests;

use Nucleus\Framework\Nucleus;
use Nucleus\IService\EventDispatcher\IEvent;
use Nucleus\IService\EventDispatcher\IEventDispatcherService;

/**
 * Description of NucleusIntegrationTest
 *
 * @author Martin
 */
class NucleusIntegrationTest extends \PHPUnit_Framework_TestCase
{
    private $nucleus = null;

    public function setUp()
    {
        $this->nucleus = Nucleus::factory(__DIR__ . '/fixtures/integrationTest.json');
    }

    public function testListenConnection()
    {
        $serviceContainer = $this->nucleus->getServiceContainer();
        $serviceDispatcher = $serviceContainer->getServiceByName(IEventDispatcherService::NUCLEUS_SERVICE_NAME);
        $serviceForTest = $serviceContainer->getServiceByName("serviceForTest");

        /* @var $serviceDispatcher Nucleus\IService\EventDispatcher\IEventDispatcherService */
        $parameter = "namedParameter";
        $event = $serviceDispatcher->dispatch("Test", $this, array("namedParameter" => $parameter));

        $this->assertSame($event, $serviceForTest->event);
        $this->assertSame($this, $serviceForTest->typedParameter);
        $this->assertEquals(10, $serviceForTest->defaultValue);
        $this->assertEquals($parameter, $serviceForTest->namedParameter);
    }

    public function testRouteConnection()
    {
        $serviceContainer = $this->nucleus->getServiceContainer();
        $serviceRouter = $serviceContainer->getServiceByName('routing');
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

    public function testLoadServices()
    {
        $serviceContainer = $this->nucleus->getServiceContainer();
        foreach ($serviceContainer->getServiceNames() as $name) {
            $serviceContainer->getServiceByName($name);
        }
        //If we reach that point this mean that no exception have been triggered
        $this->assertTrue(true);
    }
}

class ServiceForTest
{
    public $event = null;
    public $namedParameter = null;
    public $typedParameter = null;
    public $defaultValue = null;

    public function reset()
    {
        foreach (get_object_vars($this) as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @Listen("Test")
     */
    public function listen(IEvent $event, $namedParameter, NucleusIntegrationTest $typedParameter, $defaultValue = 10)
    {
        foreach (get_defined_vars() as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @Route(name="test",path="/test",defaults={"test" = 0})
     * @I18nRoute(
     *      name="test-i18n",path="/test-i18n",defaults={"test" = 0},
     *      routes={
     *          "en-us" = @Route(path="/test-en-us")
     *      }
     * )
     */
    public function route()
    {
        
    }
}
