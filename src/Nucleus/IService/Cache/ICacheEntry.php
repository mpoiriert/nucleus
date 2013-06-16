<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Cache;

/**
 *
 * @author Martin
 */
interface ICacheEntry
{

    /**
     * Get the value from cache or throw a exception if value not found
     * 
     * @return mixed
     * 
     * @throws Mpoiriert\IService\Cache\ValueNotFoundException
     */
    public function get($forceRepoll = false);

    /**
     * Set the value in cache with a specified lifetime. The value will be
     * serialized so be carefull and use the __sleep and __wake_up magic function.
     * 
     * @param mixed $value
     * @param int $lifetime The lifetime in seconds. Any 0 or negative value will be treated as infinit
     */
    public function set($value, $lifetime = 0);

    /**
     * Delete the current cache entry. If the entry doesn't exists nothing will
     * happen.
     */
    public function delete();

    /**
     * Return the timestamp when the entry was updated
     *
     * @return int
     */
    public function getUpdateTimestamp();

    /**
     * Return the last lifetime set on the entry
     * 
     * @return integer
     */
    public function getLifetime();

    /**
     * @return \Nucleus\IService\Cache\ICacheCategory
     */
    public function getCategory();
}