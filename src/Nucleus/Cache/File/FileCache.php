<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Cache\File;

use Nucleus\IService\Cache\ICacheService;
use Nucleus\Framework\Nucleus;
use Nucleus\IService\FileSystem\IFileSystemService;
use Nucleus\IService\Cache\EntryNotFoundException;
use Nucleus\Cache\Entry;

/**
 * Description of FileCache
 *
 * @author Martin
 */
class FileCache implements ICacheService
{
    /**
     * @var IFileSystemService 
     */
    private $fileSystem;
    
    private $cachePath;
    
    /**
     * @param \Nucleus\IService\FileSystem\IFileSystemService $fileSystem
     * 
     * @Inject(cachePath="$[configuration][generatedDirectory]")
     */
    public function initialize(IFileSystemService $fileSystem, $cachePath)
    {
        $this->fileSystem = $fileSystem;
        $this->cachePath = $cachePath . '/fileCache';
    }
    
    public function clearAllNamespaces()
    {
        $this->fileSystem->remove($this->cachePath);
    }

    public function clearNamespace($namespace = ICacheService::NAMESPACE_DEFAULT)
    {
        $this->fileSystem->remove($this->cachePath . '/' . $this->sanitize($namespace));
    }

    public function get($name, $namespace = ICacheService::NAMESPACE_DEFAULT)
    {
        $file = $this->getFile($name, $namespace);
        if(!$this->fileSystem->exists($file)) {
            throw new EntryNotFoundException(EntryNotFoundException::formatMessage($name, $namespace));
        }
        
        $entry = $this->loadEntry($file);
        
        if(!($entry instanceof Entry)) {
            throw new EntryNotFoundException(EntryNotFoundException::formatMessage($name, $namespace));
        }
        
        if($entry->isExpired()) {
            throw new EntryNotFoundException(EntryNotFoundException::formatMessage($name, $namespace));
        }
        
        return $entry->getValue();
    }
    
    /**
     * @param string $file
     * @return Entry
     */
    private function loadEntry($file)
    {
        return unserialize(file_get_contents($file));
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
        $entry = new Entry($name, $namespace, $value, $timeToLive, time());
        $file = $this->getFile($name, $namespace);
        $this->fileSystem->dumpFile($file, serialize($entry));
    }
    
    private function getFile($name, $namespace)
    {
        $namespace = $this->sanitize($namespace);
        $name = $this->sanitize($name);
        return $this->cachePath . '/' . $namespace . '/' . $name . '.php';
    }
    
    private function sanitize($string)
    {
        return preg_replace('/[^\w\-~_\.]+/u', '-', $string);
    }
    
    /**
     * @param mixed $configuration
     * @return BusinessRuleEngine
     */
    public static function factory($configuration = null)
    {
        if (is_null($configuration)) {
            $configuration = __DIR__ . '/nucleus.json';
        }

        return Nucleus::serviceFactory($configuration, 'cache.file');
    }
}
