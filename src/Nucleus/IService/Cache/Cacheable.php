<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Cache;

/**
 * Description of Caching
 *
 * @author Martin
 * 
 * @Annotation
 * 
 * @Target({"METHOD"})
 */
class Cacheable
{
    /**
     * @var integer
     */
    public $timeToLive = 0;

    /**
     * @var string
     */
    public $namespace = ICacheService::NAMESPACE_DEFAULT;
}
