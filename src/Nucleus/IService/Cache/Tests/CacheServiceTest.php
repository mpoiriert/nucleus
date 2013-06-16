<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Cache\Tests;

use Nucleus\IService\Cache\CategoryDoesNotExistsException;
use Nucleus\IService\Cache\ValueNotFoundException;
use Nucleus\IService\Cache\ICacheCategory;

/**
 * Description of CacheServiceTest
 *
 * @author Martin
 */
abstract class CacheServiceTest extends \PHPUnit_Framework_TestCase
{
    private $cacheService;
    private $categories = array(
        ICacheCategory::NAME_DEFAULT,
        ICacheCategory::NAME_SYSTEM
    );

    /**
     * @return \Nucleus\IService\Cache\ICacheService
     */
    abstract protected function getCacheService($configuration);

    /**
     * @return \Nucleus\IService\Cache\ICacheService
     */
    private function loadCacheService()
    {
        if (is_null($this->cacheService)) {
            $this->cacheService = $this->getCacheService(
                array("categories" => $this->categories)
            );
            $this->assertInstanceOf('\Nucleus\IService\Cache\ICacheService', $this->cacheService);
        }

        return $this->cacheService;
    }

    public function testGetCategory()
    {
        $cacheService = $this->loadCacheService();

        $category = $cacheService->getCategory(ICacheCategory::NAME_DEFAULT);
        $this->assertInstanceOf('\Nucleus\IService\Cache\ICacheCategory', $category);

        try {
            $cacheService->getCategory("notExisting");
            $this->fail('Must throw a exception when requested a not existing category.');
        } catch (CategoryDoesNotExistsException $e) {
            $this->assertTrue(true);
        }
    }

    public function testGetAllCategoryNames()
    {
        $cacheService = $this->loadCacheService();

        $categoryNames = $cacheService->getAllCategoryNames();

        $this->assertCount(0, array_diff($this->categories, $categoryNames), 'Some categories exists that are not supposed to.');
    }

    public function testGetAllCategories()
    {
        $cacheService = $this->loadCacheService();
        $categoryNames = array();
        foreach ($cacheService->getAllCategories() as $category) {
            $this->assertInstanceOf('\Nucleus\IService\Cache\ICacheCategory', $category);
            $categoryNames[] = $category->getName();
        }

        $this->assertCount(0, array_diff($this->categories, $categoryNames), 'Some categories exists that are not supposed to.');
    }

    public function testEntryFactory()
    {
        $cacheService = $this->loadCacheService();
        $entryNamename = uniqid();
        $entry = $cacheService->entryFactory($entryNamename, ICacheCategory::NAME_DEFAULT);

        $this->assertEquals(ICacheCategory::NAME_DEFAULT, $entry->getCategory()->getName());

        //This delete is just to prevent that a entry could be there due to a
        //test failure
        $entry->delete();

        try {
            $entry->get();
            $this->fail('Must throw a since the entry does not exists.');
        } catch (ValueNotFoundException $e) {
            $this->assertTrue(true);
        }

        $entry->set("value", 10);
        $this->assertEquals("value", $entry->get());
        $this->assertEquals(10, $entry->getLifetime());

        $entry->delete();

        try {
            $entry->get();
            $this->fail('Must throw a since we deleted the entry.');
        } catch (ValueNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    public function testEntryRepoll()
    {
        $cacheService = $this->loadCacheService();
        $entryNamename = uniqid();
        $entry = $cacheService->entryFactory($entryNamename, ICacheCategory::NAME_DEFAULT);

        //This delete is just to prevent that a entry could be there due to a
        //test failure
        $entry->delete();

        $entry->set("test");

        $newEntry = $cacheService->entryFactory($entryNamename, ICacheCategory::NAME_DEFAULT);

        $this->assertNotSame($newEntry, $entry);

        $this->assertEquals("test", $newEntry->get());

        $newEntry->set("toto");

        $this->assertNotEquals("toto", $entry->get());
        $this->assertEquals("toto", $entry->get(true));

        $newEntry->delete();

        $this->assertEquals("toto", $entry->get());

        try {
            $entry->get(true);
            $this->fail('Must throw a since we deleted the entry from another object but use true on repoll.');
        } catch (ValueNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    public function testCategory()
    {
        $cacheService = $this->loadCacheService();
        $entryName = uniqid();
        $entry = $cacheService->entryFactory($entryName, ICacheCategory::NAME_DEFAULT);


        //This delete is just to prevent that a entry could be there due to a
        //test failure
        $entry->delete();

        $entry->set("test");

        $this->assertEquals("test", $entry->get());

        $entry->getCategory()->clear();

        $this->assertEquals("test", $entry->get());

        try {
            $entry->get(true);
            $this->fail('Must throw a since we clear the category the entry was in.');
        } catch (ValueNotFoundException $e) {
            $this->assertTrue(true);
        }
    }
}