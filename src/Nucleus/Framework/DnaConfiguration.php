<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Framework;

/**
 * This is the configuration class need to initialize a nucleus instance.
 *
 * @author Martin
 */
class DnaConfiguration
{
    private $rootDirectory;
    private $debug = true;
    private $cachePath;
    private $configuration;
    private $aspectIncludePaths = array();
    private $aspectExcludePaths = array();
    private $addSaltToCache = true;
    private $cachePathIsFreeze = false;

    public function __construct($rootDirectory = null)
    {
        if(is_null($rootDirectory)) {
            $result = stream_resolve_include_path('composer/ClassLoader.php');
            if(!$result) {
                throw new InvalidArgumentException('Cannot determine the vendor directory.');
            }
            
            $rootDirectory = realpath(dirname($result) . '/../..');
        }
        $this->rootDirectory = $rootDirectory;
        $this->initializeDefaults();
    }

    protected function initializeDefaults()
    {
        $this->configuration = $this->rootDirectory . "/nucleus.json";
        $this->cachePath = sys_get_temp_dir() . '/nucleus';
    }

    public function getUniqueId()
    {
        return md5(serialize($this));
    }

    public function getRootDirectory()
    {
        return $this->rootDirectory;
    }

    public function getDebug()
    {
        return $this->debug;
    }

    public function getCachePath()
    {
        if ($this->cachePath && $this->addSaltToCache) {
            $cachePath = $this->cachePath . '/' . $this->getUniqueId();
        } else {
            $cachePath = $this->cachePath;
        }
       
        return $cachePath;
    }
    
    /**
     * Freez the cache path so it cannot be change even if some attribute of
     * the Dna is change. This should only be call by the framework.
     * 
     * @return DnaConfiguration
     */
    public function freezeCachePath()
    {
        $this->cachePath = $this->getCachePath();
        $this->setAddSaltToCache(false);
        $this->cachePathIsFreeze = true;
        return $this;
    }
    
    /**
     * @return boolean
     */
    public function getCachePathIsFreeze()
    {
        return $this->cachePathIsFreeze;
    }

    /**
     * @param string $path
     * @return DnaConfiguration
     * @throws \LogicException
     */
    public function setCachePath($path)
    {
        if($this->cachePathIsFreeze) {
            throw new \LogicException('The cache path has been freeze, you cannot modify any aspect of it.');
        }
        $this->cachePath = $path;
        return $this;
    }

    /**
     * @param type $debug
     * @return DnaConfiguration
     */
    public function setDebug($debug)
    {
        $this->debug = (bool) $debug;
        return $this;
    }

    /**
     * 
     * @param type $addSalt
     * @return DnaConfiguration
     * @throws \LogicException
     */
    public function setAddSaltToCache($addSalt)
    {
        if($this->cachePathIsFreeze) {
            throw new \LogicException('The cache path has been freeze, you cannot modify any aspect of it.');
        }
        $this->addSaltToCache = (boolean) $addSalt;
        return $this;
    }
    
    /**
     * @return boolean
     */
    public function getAddSaltToCache()
    {
        return $this->addSaltToCache;
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param mixed $configuration
     * @return DnaConfiguration
     * 
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * @return array
     */
    public function getAspectIncludePaths()
    {
        return $this->aspectIncludePaths;
    }
    
    /**
     * 
     * @param array $value
     * @return DnaConfiguration
     */
    public function setAspectIncludePaths(array $value)
    {
        $this->aspectIncludePaths = $value;
        return $this;
    }
    
    /**
     * @return array
     */
    public function getAspectExcludePaths()
    {
        return $this->aspectExcludePaths;
    }
    
    /**
     * 
     * @param array $value
     * @return DnaConfiguration
     */
    public function setAspectExcludePaths(array $value)
    {
        $this->aspectExcludePaths = $value;
        return $this;
    }

    public function getAspectConfiguration()
    {
        $cacheDir = $this->getDebug() ? null : $this->getCachePath() . '/aop';
        
        return array(
            'appDir' => $this->getRootDirectory(),
            'debug' => $this->getDebug(),
            'cacheDir' => $cacheDir,
            'includePaths' => $this->getAspectIncludePaths(),
            'excludePaths'   => $this->getAspectExcludePaths()
        );
    }
}