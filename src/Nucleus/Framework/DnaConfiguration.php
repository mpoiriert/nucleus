<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Framework;

use ReflectionObject;

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
    private $aspectIncludesPaths;
    private $addSaltToCache = true;
    private $cachePathIsFreeze = false;

    public function __construct($rootDirectory)
    {
        $this->rootDirectory = $rootDirectory;
        $this->initializeDefaults();
    }

    protected function initializeDefaults()
    {
        $this->configuration = $this->rootDirectory . "/nucleus.json";
        $this->aspectIncludesPaths = array();
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

    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param type $configuration
     * @return \Nucleus\Framework\DnaConfiguration
     * 
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
        return $this;
    }

    public function getAspectIncludePaths()
    {
        return $this->aspectIncludesPaths;
    }

    public function getAspectConfiguration()
    {
        $cacheDir = $this->getDebug() ? null : $this->getCachePath() . '/aop';
        
        return array(
            'appDir' => $this->getRootDirectory(),
            'debug' => $this->getDebug(),
            'cacheDir' => $cacheDir,
            'includePaths' => $this->getAspectIncludePaths()
        );
    }

    /**
     * 
     * @param \Nucleus\Framework\SingletonApplicationKernel $application
     * @return \Nucleus\Framework\DnaConfiguration
     */
    public static function factory(SingletonApplicationKernel $application)
    {
        $reflectionObject = new ReflectionObject($application);
        $classDirectory = dirname($reflectionObject->getFileName());
        $dna = new DnaConfiguration($classDirectory);
        return $dna;
    }
}