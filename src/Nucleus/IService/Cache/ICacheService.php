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
     * Return all the existings category names
     */
    public function getAllCategoryNames();

    /**
     * Return all the existings categories
     * 
     * @return Nucleus\IService\Cache\ICacheCategory[]
     */
    public function getAllCategories();

    /**
     * Return a cache category by it's name. Mainly use to call clear on it.
     * 
     * @param string $name
     * 
     * @return Nucleus\IService\Cache\ICacheCategory
     */
    public function getCategory($name);

    /**
     * Return a entry of cache. From the entry you will be able to call the function
     * get/set/delete/...
     * 
     * @param string $name
     * @param string $categoryName
     * 
     * @return Nucleus\IService\Cache\ICacheEntry
     */
    public function entryFactory($name, $categoryName = ICacheCategory::NAME_DEFAULT);
}
