<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Cache\File;

use Nucleus\IService\Cache\Tests\CacheServiceTest;
use Nucleus\Cache\Memcache\Memcache;
use ReflectionObject;

/**
 * Description of FileCacheTest
 *
 * @author Martin
 */
class MemcacheTest extends CacheServiceTest
{
    protected function getCacheService()
    {
        $memcache =  Memcache::factory();
        $reflectionObject = new ReflectionObject($memcache);
        $memcacheProperty = $reflectionObject->getProperty('memcache');
        $memcacheProperty->setAccessible(true);
        try {
            $memcacheProperty->getValue($memcache)->getExtendedStats();
        } catch (\Exception $e) {
            $this->markTestSkipped('Exception on memcache :' . $e->getMessage());
        }
        
        return $memcache;
    }    
}
