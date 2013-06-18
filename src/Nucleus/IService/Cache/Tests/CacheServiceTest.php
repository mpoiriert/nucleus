<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Cache\Tests;

use Nucleus\IService\Cache\EntryNotFoundException;
use Nucleus\IService\Cache\ICacheService;
use PHPUnit_Framework_TestCase;

/**
 * Description of CacheServiceTest
 *
 * @author Martin
 */
abstract class CacheServiceTest extends PHPUnit_Framework_TestCase
{
    private $cacheService;

    /**
     * @return ICacheService
     */
    abstract protected function getCacheService();

    /**
     * @return ICacheService
     */
    private function loadCacheService()
    {
        if (is_null($this->cacheService)) {
            $this->cacheService = $this->getCacheService();
            $this->assertInstanceOf('\Nucleus\IService\Cache\ICacheService', $this->cacheService);
            $this->cacheService->clearAllNamespaces();
        }

        return $this->cacheService;
    }

    public function testGetSetHas()
    {
        $service = $this->loadCacheService();
        
        $this->assertFalse($service->has('name'));
        
        try {
            $service->get('name');
            $this->fail('Must throw a exception of type [Nucleus\IService\Cache\EntryNotFoundException]');
        } catch(EntryNotFoundException $e) {
            $this->assertInstanceOf('Nucleus\IService\Cache\EntryNotFoundException',$e);
        }
        
        $service->set('name','value');
        
        $this->assertTrue($service->has('name'));
        
        $this->assertEquals('value',$service->get('name'));
    }
    
    public function testClearNamespace()
    {
        $service = $this->loadCacheService();
        $namespace1 = uniqid();
        $namespace2 = uniqid();
        $this->assertFalse($service->has('name',$namespace1));
        $this->assertFalse($service->has('name',$namespace2));
        $service->set('name','value',0,$namespace1);
        $service->set('name','value',0,$namespace2);
        $this->assertTrue($service->has('name',$namespace1));
        $this->assertTrue($service->has('name',$namespace2));
        $service->clearNamespace($namespace1);
        $this->assertFalse($service->has('name',$namespace1));
        $this->assertTrue($service->has('name',$namespace2));
    }
    
    public function testClearNamespaces()
    {
        $service = $this->loadCacheService();
        $namespaces = array(uniqid(),  uniqid());
        foreach($namespaces as $namespace) {
           $this->assertFalse($service->has('name',$namespace));
        }
        
        foreach($namespaces as $namespace) {
           $service->set('name', 'value', 0, $namespace);
        }
        
        foreach($namespaces as $namespace) {
           $this->assertTrue($service->has('name',$namespace));
        }
        
        $service->clearAllNamespaces();
        
        foreach($namespaces as $namespace) {
           $this->assertFalse($service->has('name',$namespace));
        }
    }
}