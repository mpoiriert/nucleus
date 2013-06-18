<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Cache\File;

use Nucleus\IService\Cache\Tests\CacheServiceTest;
use Nucleus\Cache\File\FileCache;

/**
 * Description of FileCacheTest
 *
 * @author Martin
 */
class FileCacheTest extends CacheServiceTest
{
    protected function getCacheService()
    {
        return FileCache::factory();
    }    
}
