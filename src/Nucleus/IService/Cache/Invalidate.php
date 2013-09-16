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
class Invalidate
{
    /**
     * The cache key name if specified. Can use the parameters of the entry
     * to replace some value. Use in junction of the Cacheable annotation
     * 
     * Ex: keyName="salt.$paramName1,$paramName2" 
     * 
     * @var string
     */
    public $keyName;

    /**
     * @var string
     */
    public $namespace = ICacheService::NAMESPACE_DEFAULT;
}
