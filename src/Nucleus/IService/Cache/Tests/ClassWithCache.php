<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Cache\Tests;

/**
 * Description of ClassWithCache
 *
 * @author Martin
 */
class ClassWithCache
{
    /**
     * @param string $name
     * @param string $value
     * 
     * @\Nucleus\IService\Cache\Cacheable(namespace="test",keyName="test.$name")
     */
    public function get($name, $value)
    {
        return $value;
    }
    
    /**
     * @param string $name
     * 
     * @\Nucleus\IService\Cache\Invalidate(namespace="test",keyName="test.$name")
     */
    public function delete($name)
    {
        
    }
}
