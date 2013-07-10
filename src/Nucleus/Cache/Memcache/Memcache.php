<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Cache\Memcache;

use Nucleus\IService\Cache\ICacheService;
use Nucleus\Cache\BaseCacheService;
use Nucleus\Framework\Nucleus;
use Nucleus\IService\Cache\EntryNotFoundException;

/**
 * Description of Memcache
 *
 * @author Martin
 */
class Memcache extends BaseCacheService
{
    static private $globalSalt = "__GLOBAL__";
    
    /**
     *
     * @var \Memcache
     */
    private $memcache;
    
    private $namespaceSalts = array();
    
    /**
     * 
     * @param \Memcache $memcache
     * 
     * @Inject
     */
    public function setMemcache(\Memcache $memcache = null) 
    {
        if(is_null($memcache)) {
            $memcache = new \Memcache();
            $memcache->addserver('localhost');
        }
        $this->memcache = $memcache;
    }
    
    public function clearAllNamespaces()
    {
        $this->clearNamespace(self::$globalSalt);
    }

    public function clearNamespace($namespace = ICacheService::NAMESPACE_DEFAULT)
    {
        $salt = $this->getNamespaceSalt($namespace);
        $salt++;
        $this->setNamespaceSalt($namespace, $salt);
        $this->namespaceSalts[$namespace] = $salt;
    }

    public function get($name, $namespace = ICacheService::NAMESPACE_DEFAULT)
    {
        $key = $this->getKey($name, $namespace);
        $entry = $this->memcache->get($key);
        if(!$entry) {
            throw new EntryNotFoundException(EntryNotFoundException::formatMessage($name, $namespace));
        }
        
        return $this->getEntryValue($entry, $name, $namespace);
    }

    public function has($name, $namespace = ICacheService::NAMESPACE_DEFAULT)
    {
        try {
            $this->get($name,$namespace);
            return true;
        } catch(EntryNotFoundException $e) {
            return false;
        }
    }

    public function set($name, $value, $timeToLive = 0, $namespace = ICacheService::NAMESPACE_DEFAULT)
    {
        $entry = $this->createEntry($name, $value, $timeToLive, $namespace);
        $key = $this->getKey($name, $namespace);
        $this->memcache->set($key,$entry);
    }
    
    private function getKey($name, $namespace)
    {
        $salt = $this->getNamespaceSalt($namespace);
        $globalSalt = $this->getNamespaceSalt(self::$globalSalt);
        return md5(serialize(compact('globalSalt','salt','name','namespace')));
    }
    
    private function getNamespaceSalt($namespace)
    {
        if(!isset($this->namespaceSalts[$namespace])) {
            $metadata = $this->memcache->get($namespace . '.metadata');
            if(!$metadata) {
                $metadata = 1;
                $this->setNamespaceSalt($namespace, $metadata);
            }
            $this->namespaceSalts[$namespace] = $metadata;
        }
        
        return $this->namespaceSalts[$namespace];
    }
    
    private function setNamespaceSalt($namespace, $value)
    {
        $this->memcache->set($namespace . '.metadata', $value);
    }
    
    /**
     * @param mixed $configuration
     * @return Memcache
     */
    public static function factory($configuration = null)
    {
        if (is_null($configuration)) {
            $configuration = __DIR__ . '/nucleus.json';
        }

        return Nucleus::serviceFactory($configuration, 'cache.memcache');
    }
}
