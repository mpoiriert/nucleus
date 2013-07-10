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
    public function has($name, $namespace = ICacheService::NAMESPACE_DEFAULT)
    {
        try {
            $this->get($name, $namespace);
            return true;
        } catch (EntryNotFoundException $e) {
            return false;
        }
    }
    
    protected function getEntryValue($serialized, $name, $namespace) 
    {
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
        return unserialize($serialized);
    }
}
