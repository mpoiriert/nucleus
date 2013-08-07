<?php

namespace Nucleus\IService\DependencyInjection\Tests;

use Nucleus\IService\DependencyInjection\ServiceDoesNotExistsException;
use Nucleus\IService\DependencyInjection\ServiceDisabledException;
use Nucleus\IService\DependencyInjection\ILifeCycleAware;

abstract class ServiceContainerTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Nucleus\IService\DependencyInjection\IServiceContainer;
     */
    private $serviceContainer;

    /**
     * @return \Nucleus\IService\DependencyInjection\IServiceContainer
     */
    abstract protected function getServiceContainer($configuration);

    /**
     * @return \Nucleus\IService\DependencyInjection\IServiceContainer
     */
    private function loadServiceContainer()
    {
        if (is_null($this->serviceContainer)) {
            $this->serviceContainer = $this->getServiceContainer(
                array('services' => array(
                        'test' => array(
                            'class' => __NAMESPACE__ . '\TestService',
                            'configuration' => 'configuration_string'
                        ),
                        'disabled' => array('disabled' => true),
                        'tag' => array(
                            'class' => __NAMESPACE__ . '\Tagged',
                        ),
                        'itag' => array(
                            'class' => __NAMESPACE__ . '\TaggedViaInterface',
                        ),
                        'injected' => array(
                            'class' => __NAMESPACE__ . '\TestInjectedService',
                        ),
                    )
                )
            );
            $this->assertInstanceOf('\Nucleus\IService\DependencyInjection\IServiceContainer', $this->serviceContainer);
        }

        return $this->serviceContainer;
    }

    public function testGetServiceByName()
    {
        $serviceContainer = $this->loadServiceContainer();
        $serviceTest = $serviceContainer->getServiceByName('test');

        $this->assertInstanceOf(__NAMESPACE__ . '\TestService', $serviceTest);

        $this->assertSame($serviceTest, $serviceContainer->getServiceByName('test'));

        try {
            $serviceContainer->getServiceByName('unknow');
            $this->fail('Must throw a exception when requested a not existing service.');
        } catch (ServiceDoesNotExistsException $e) {
            $this->assertTrue(true);
        }

        try {
            $serviceContainer->getServiceByName('disabled');
            $this->fail('Must throw a exception when requested a disabled service.');
        } catch (ServiceDisabledException $e) {
            $this->assertTrue(true);
        }
    }

    public function testGetServiceNames()
    {
        $serviceNames = $this->loadServiceContainer()->getServiceNames();
        $this->assertCount(0, array_diff(array('test', 'tag', 'itag', 'injected'), $serviceNames));
    }

    public function testGetServiceConfiguration()
    {
        $serviceContainer = $this->loadServiceContainer();
        $configurationTest = $serviceContainer->getServiceConfiguration('test');
        $this->assertEquals('configuration_string', $configurationTest, 'Returned service configuration is not good');

        $configurationTag = $serviceContainer->getServiceConfiguration('tag');
        $this->assertNull($configurationTag, 'Returned service configuration should be null');

        try {
            $serviceContainer->getServiceConfiguration('unknow');
            $this->fail('Must throw a exception when requested configuration of a not existing service.');
        } catch (ServiceDoesNotExistsException $e) {
            $this->assertTrue(true);
        }
    }

    public function testGetServiceByTag()
    {
        $serviceContainer = $this->loadServiceContainer();
        $iTestServices = $serviceContainer->getServicesByTag('ITest');

        $this->assertEquals(1, count($iTestServices));

        $this->assertInstanceOf(__NAMESPACE__ . '\TaggedViaInterface', $iTestServices[0]);

        $testServices = $serviceContainer->getServicesByTag('Test');

        $this->assertEquals(2, count($testServices));

        $this->assertTrue(in_array($iTestServices[0], $testServices));

        $this->assertEquals(
            0, count($serviceContainer->getServicesByTag('Unknow'))
        );
    }

    public function testInjection()
    {
        $serviceContainer = $this->loadServiceContainer();
        $injectedService = $serviceContainer->getServiceByName('injected');
        $this->assertInstanceOf(__NAMESPACE__ . '\TestInjectedService', $injectedService);

        $tagService = $serviceContainer->getServiceByName("tag");
        $itagService = $serviceContainer->getServiceByName("itag");

        $this->assertSame($tagService, $injectedService->getServiceTag());
        $this->assertSame($itagService, $injectedService->getServiceItag());
        $this->assertSame(array($itagService), $injectedService->getServices());
    }

    public function testStart()
    {
        $service = $this->loadServiceContainer()->getServiceByName("test");
        $this->assertTrue($service->started);
    }

    public function testShutdown()
    {
        $container = $this->loadServiceContainer();
        $service = $container->getServiceByName("test");
        $container->shutdown();
        $this->assertTrue($service->shutdowned);
    }
}
if (!class_exists('Nucleus\DependencyInjection\Tests\TestService')) {

    class TestService implements ILifeCycleAware
    {
        public $started = false;
        public $shutdowned = false;

        public function serviceShutdown()
        {
            $this->shutdowned = true;
        }

        public function serviceStart()
        {
            $this->started = true;
        }
    }

    class TestInjectedService
    {
        private $serviceTag;
        private $serviceItag;
        private $services;

        /**
         * @\Nucleus\IService\DependencyInjection\Inject(serviceTag="tag")
         */
        public function injectService($serviceTag, $itag)
        {
            $this->serviceTag = $serviceTag;
            $this->serviceItag = $itag;
        }

        /**
         * @\Nucleus\IService\DependencyInjection\Inject(services="@ITest")
         */
        public function injectServiceByTag($services)
        {
            $this->services = $services;
        }

        public function getServiceTag()
        {
            return $this->serviceTag;
        }

        public function getServiceItag()
        {
            return $this->serviceItag;
        }

        public function getServices()
        {
            return $this->services;
        }
    }

    /**
     * @\Nucleus\IService\DependencyInjection\Tag("Test")
     */
    class Tagged
    {
        
    }

    /**
     * @\Nucleus\IService\DependencyInjection\Tag("ITest")
     */
    interface ITagged
    {
        
    }

    /**
     * @\Nucleus\IService\DependencyInjection\Tag("Test")
     */
    class TaggedViaInterface implements ITagged
    {
        
    }
}