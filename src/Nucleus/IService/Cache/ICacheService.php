<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Cache;

/**
 *
 * @author mpoirier
 */
interface ICacheService
{
    /**
     * The service name use as a reference
     */
    const NUCLEUS_SERVICE_NAME = 'cache';
    
    /**
     * The default cache namespace
     */
    const NAMESPACE_DEFAULT = 'default';
    
    /**
     * Return a value from the cache.
     * 
     * @param string $name
     * @param string $namespace
     * 
     * @throws EntryNotFoundException Thrown if the value is not in the cache
     * 
     * @return mixed
     */
    public function get($name, $namespace = ICacheService::NAMESPACE_DEFAULT);
    
    /**
     * Set a value in the cache. The value must be serialize by the cache
     * service if cannot be stored as is.
     * 
     * @param string $name
     * @param mixed $value
     * @param int $timeToLive The delay before the value will be lost, 0 for none
     * @param string $namespace
     */
    public function set($name, $value, $timeToLive = 0, $namespace = ICacheService::NAMESPACE_DEFAULT);
    
    /**
     * Verify is a value is still in the cache
     * 
     * @param string $name
     * @param string $namespace
     * 
     * @return boolean
     */
    public function has($name, $namespace = ICacheService::NAMESPACE_DEFAULT);
    
    /**
     * Delete a cache entry base on it's name and namespace
     * 
     * @param string $name
     * @param string $namespace
     */
    public function delete($name, $namespace = ICacheService::NAMESPACE_DEFAULT);
    
    /**
     * Clear a specific namespace
     * 
     * @param string $namespace
     */
    public function clearNamespace($namespace = ICacheService::NAMESPACE_DEFAULT);
    
    /**
     * Clear all the namespaces
     */
    public function clearAllNamespaces();
}
