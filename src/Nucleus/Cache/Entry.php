<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Cache;

/**
 * Description of Entry
 *
 * @author Martin
 */
class Entry
{
    private $value;
    private $timeToLive;
    private $creationTime;
    private $name;
    private $namespace;
    
    public function __construct($name, $namespace,  $value, $timeToLive = 0, $creationTime = null)
    {
        $this->namespace = $namespace;
        $this->name = $name;
        $this->value = $value;
        $this->timeToLive = $timeToLive;
        $this->creationTime = is_null($creationTime) ? time() : $creationTime;
    }
    
    public function isExpired()
    {
        if($this->timeToLive === 0) {
            return false;
        }
        return $this->creationTime + $this->timeToLive < time();
    }
    
    public function isOlderThan($timestamp)
    {
        return $this->creationTime < $timestamp;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getNamespace() 
    {
        return $this->namespace;
    }
    
    public function getValue()
    {
        return $this->value;
    }
    
    public function getTimeToLive()
    {
        return $this->timeToLive;
    }
    
    public function getCreationTime()
    {
        return $this->creationTime;
    }
}
