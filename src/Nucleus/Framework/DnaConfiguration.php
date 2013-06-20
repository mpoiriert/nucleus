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
    private $debug = false;
    private $cachePath;
    private $configuration;
    private $appLoaderPath;
    private $autoloadPaths;
    private $aspectIncludesPaths;
    private $addSaltToCache = true;
    
    public function __construct($rootDirectory)
    {
        $this->rootDirectory = $rootDirectory;
        $this->initializeDefaults();
    }
    
    protected function initializeDefaults()
    {
        $this->configuration = $this->rootDirectory . "/nucleus.json";
        $this->appLoaderPath = $this->rootDirectory . "/vendor/autoload.php";
        
        $vendorDirectory = dirname($this->appLoaderPath);
        
        $this->aspectIncludesPaths = array();
        
        $this->autoloadPaths = array(
            'Go' => $vendorDirectory . '/lisachenko/go-aop-php/src',
            'TokenReflection' => $vendorDirectory . '/andrewsville/php-token-reflection',
            'Doctrine\\Common' => $vendorDirectory . '/doctrine/common/lib',
            'Dissect' => $vendorDirectory . '/jakubledl/dissect/src',
            'Nucleus\\Framework' => realpath(__DIR__ . "/../..")
        );
        
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
        if($this->cachePath && $this->addSaltToCache) {
            return $this->cachePath . '/' . $this->getUniqueId();
        }
        return $this->cachePath;
    }
    
    /**
     * @param string $path
     * @return DnaConfiguration
     */
    public function setCachePath($path)
    {
        $this->cachePath = $path;
        return $this;
    }
    
    /**
     * @param boolean $addSalt
     * @return DnaConfiguration
     */
    public function setAddSaltToCache($addSalt)
    {
        $this->addSaltToCache = (boolean)$addSalt;
        return $this;
    }
    
    public function getConfiguration()
    {
        return $this->configuration;
    }
    
    public function getAppLoaderPath()
    {
        return $this->appLoaderPath;
    }
    
    public function getAutoloadPaths()
    {
        return $this->autoloadPaths;
    }
    
    public function getAspectIncludePaths()
    {
        return $this->aspectIncludesPaths;
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