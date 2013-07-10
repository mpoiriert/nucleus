<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Cache\File;

use Nucleus\IService\Cache\ICacheService;
use Nucleus\Framework\Nucleus;
use Nucleus\IService\FileSystem\IFileSystemService;
use Nucleus\Cache\BaseCacheService;

/**
 * Description of FileCache
 *
 * @author Martin
 */
class FileCache extends BaseCacheService
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

    public function recoverEntry($name, $namespace)
    {
        $file = $this->getFile($name, $namespace);
        if(!$this->fileSystem->exists($file)) {
            return null;
        }
        
        return file_get_contents($file);
    }

    public function storeEntry($name, $entry, $timeToLive, $namespace)
    {
        $file = $this->getFile($name, $namespace);
        $this->fileSystem->dumpFile($file, $entry);
    }
    
    private function getFile($name, $namespace)
    {
        $namespace = $this->sanitize($namespace);
        $name = $this->sanitize($name);
        return $this->cachePath . '/' . $namespace . '/' . $name . '.php';
    }
    
    private function sanitize($string)
    {
        $result = preg_replace('/[^\w\-~_\.]+/u', '-', $string);
        
        //This is to prevent error on filename too long on disk
        //The 80 is a arbitrary number
        if(strlen($result) >= 80) {
            return md5($result);
        }
        
        return $result;
    }
    
    /**
     * @param mixed $configuration
     * @return FileCache
     */
    public static function factory($configuration = null)
    {
        if (is_null($configuration)) {
            $configuration = __DIR__ . '/nucleus.json';
        }

        return Nucleus::serviceFactory($configuration, 'cache.file');
    }
}
