<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Nucleus\Framework\Tests;

use Nucleus\IService\EventDispatcher\IEvent;

/**
 * This class must be in it's own file since Aspect will not be parse prior to
 * the loading of the class
 */
class TestableService
{
    public $event = null;
    public $namedParameter = null;
    public $typedParameter = null;
    public $defaultValue = null;
    public $cacheCall = 0;
    public $postInitialized = false;

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
    
    /**
     * @\Nucleus\IService\Cache\Cacheable
     * @param type $uniqid
     */
    public function cache($uniqid)
    {
        $this->cacheCall++;
    }
    
    /**
     * 
     * @param TestableService $service
     * 
     * @Listen("Service.servicefortest.postInitialized")
     */
    public function listenLifeCycle(TestableService $service, IEvent $event)
    {
        if($event->getName() == 'Service.servicefortest.postInitialized' && $this === $service) {
            $this->postInitialized = true;
        }
    }
}
