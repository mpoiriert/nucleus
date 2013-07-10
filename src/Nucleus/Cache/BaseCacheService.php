<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Cache;

use Nucleus\IService\Cache\ICacheService;
use Nucleus\Cache\Entry;
use Nucleus\IService\Cache\EntryNotFoundException;

/**
 * Description of BaseCacheService
 *
 * @author Martin
 */
abstract class BaseCacheService implements ICacheService
{
    private $inactiveNamespaces = array();
    
    /**
     * @param array $namespaces
     */
    public function setInactiveNamespaces(array $namespaces) 
    {
        $this->inactiveNamespaces = $namespaces;
    }
    
    /**
     * @param string $namespace
     * @return boolean
     */
    protected function namespaceIsActive($namespace)
    {
        return !in_array($namespace,$this->inactiveNamespaces);
    }
    
    /**
     * @param string $name
     * @param string $namespace
     * @return boolean
     */
    public function has($name, $namespace = ICacheService::NAMESPACE_DEFAULT)
    {
        try {
            $this->get($name, $namespace);
            return true;
        } catch (EntryNotFoundException $e) {
            return false;
        }
    }
    
    /**
     * 
     * @param string $name
     * @param string $namespace
     * @return mixed
     * 
     * @throws EntryNotFoundException
     */
    public function get($name, $namespace = ICacheService::NAMESPACE_DEFAULT) 
    {
        if(!$this->namespaceIsActive($namespace)) {
            throw new EntryNotFoundException(EntryNotFoundException::formatMessage($name, $namespace));
        }
        
        return $this->getEntryValue($this->recoverEntry($name, $namespace), $name, $namespace);
    }
    
    /**
     * 
     * @param string $name
     * @param mixed $value
     * @param int $timeToLive
     * @param string $namespace
     */
    public function set($name, $value, $timeToLive = 0, $namespace = ICacheService::NAMESPACE_DEFAULT)
    {
        if(!$this->namespaceIsActive($namespace)) {
            return;
        }
        $entry = $this->createEntry($name, $value, $timeToLive, $namespace);
        $this->storeEntry($name, $entry, $timeToLive, $namespace);
    }
    
    abstract protected function storeEntry($name, $entry, $timeToLive, $namespace);

    /**
     * Return the string entry or null if not found
     * 
     * @return string
     */
    abstract protected function recoverEntry($name, $namespace);
    
    protected function getEntryValue($serialized, $name, $namespace) 
    {
        if(is_null($serialized)) {
            throw new EntryNotFoundException(EntryNotFoundException::formatMessage($name, $namespace));
        }
        
        $entry = $this->loadEntry($serialized);
        
        if(!($entry instanceof Entry)) {
            throw new EntryNotFoundException(EntryNotFoundException::formatMessage($name, $namespace));
        }
        
        if($entry->isExpired()) {
            throw new EntryNotFoundException(EntryNotFoundException::formatMessage($name, $namespace));
        }
        
        return $entry->getValue();
    }
    
    protected function createEntry($name, $value, $timeToLive, $namespace)
    {
        $entry = new Entry($name, $namespace, $value, $timeToLive, time());
        return serialize($entry);
    }


    /**
     * @param string $file
     * @return Entry
     */
    protected function loadEntry($serialized)
    {
        //I normally never use @ to stop warning, but there is no easy
        //way to check this
        return @unserialize($serialized);
    }
}
