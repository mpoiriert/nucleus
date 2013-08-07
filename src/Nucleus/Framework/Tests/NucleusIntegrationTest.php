<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Framework\Tests;

use Nucleus\Framework\Nucleus;
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
    
    public function testInitializedEvent()
    {
        $serviceContainer = $this->nucleus->getServiceContainer();
        $serviceForTest = $serviceContainer->getServiceByName("serviceForTest");
        $this->assertTrue($serviceForTest->postInitialized);
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

    public function testCache()
    {
        $serviceContainer = $this->nucleus->getServiceContainer();
        $serviceForTest = $serviceContainer->getServiceByName("serviceForTest");
        $uniqid = uniqid();
        $this->assertSame(0, $serviceForTest->cacheCall);
        $serviceForTest->cache($uniqid);
        $this->assertSame(1, $serviceForTest->cacheCall);
        $serviceForTest->cache($uniqid);
        $this->assertSame(1, $serviceForTest->cacheCall);
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
