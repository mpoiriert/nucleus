<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Framework;

use UnexpectedValueException;

/**
 * Singleton Application Wraper arround nucleus usefull for application that
 * will not have more than one nucleus instance running. Allow also to access
 * the nucleus from a singleton point of view. This is not always the best way
 * to work but it might be the only one some time.
 *
 * @author Martin
 */
abstract class SingletonApplicationKernel
{
    /**
     * @var SingletonApplicationKernel 
     */
    private static $instance;

    /**
     * @var Nucleus 
     */
    private $nucleus;

    /**
     * Return the configuration need to load the nucleus instance
     * 
     * @return DnaConfiguration
     */
    protected function getDnaConfiguration()
    {
        return DnaConfiguration::factory($this);
    }
    
    public function autoload($class)
    {
        $folders = array(
            realpath(__DIR__ . '/../../../vendor/lisachenko/go-aop-php/src'),
            realpath(__DIR__ . '/../..')
        );

        $filePath = str_replace('\\', '/', $class) . '.php';

        foreach ($folders as $folder) {
            $classFilePath = $folder . '/' . $filePath;
            if (file_exists($classFilePath)) {
                require $classFilePath;
                return true;
            }
        }

        return false;
    }

    /**
     * 
     * @return SingletonApplicationKernel
     * @throws \RuntimeException
     */
    static function createInstance()
    {
        global $aspectContainer;
        if (!is_null(self::$instance)) {
            throw new \RuntimeException('Nucleus application kernel instance already created');
        }

        $application = self::$instance = new static();
        
        spl_autoload_register(array($application, 'autoload'));
        
        $application->loadAspectKernelClass();
        
        $dnaConfiguration = self::$instance->getDnaConfiguration();

        if (!($dnaConfiguration instanceof DnaConfiguration)) {
            throw new UnexpectedValueException("The return value for [getDnaConfiguration] should be a instance of [Nucleus\Framework\DnaConfiguration]");
        }

        $cacheDir = $dnaConfiguration->getDebug() ? null : $dnaConfiguration->getCachePath() . '/aop';
        
        $aspectConfiguration = array(
            'appDir' => $dnaConfiguration->getRootDirectory(),
            'appLoader' => $dnaConfiguration->getAppLoaderPath(),
            'debug' => $dnaConfiguration->getDebug(),
            'cacheDir' => $cacheDir,
            'autoloadPaths' => $dnaConfiguration->getAutoloadPaths(),
            'includePaths' => $dnaConfiguration->getAspectIncludePaths()
        );
        
        if($cacheDir && !is_dir($cacheDir)) {
          mkdir($cacheDir, 0777, true);
        }

        spl_autoload_unregister(array($application, 'autoload'));

        $aspectKernel = $application->getAspectKernel();
        
        $aspectKernel->init($aspectConfiguration);

        $aspectContainer = $aspectKernel->getContainer();
        
        $application->nucleus = Nucleus::factory($dnaConfiguration);
     
        return $application;
    }
    
    /**
     * @return \Go\Core\AspectKernel
     */
    private function getAspectKernel()
    {
        return PrivateNucleusAspectKernel::getInstance();
    }
    
    protected function loadAspectKernelClass()
    {
        eval('
        namespace Nucleus\Framework 
        {
            class PrivateNucleusAspectKernel extends \Go\Core\AspectKernel
            {
                protected function configureAop(\Go\Core\AspectContainer $container)
                {
                }
            }
        }
        ');
    }

    /**
     * Get the nucleus application base on the kernel
     * 
     * @return Nucleus
     */
    public function getNucleus()
    {
        return $this->nucleus;
    }

    /**
     * @return SingletonApplicationKernel
     */
    static public function getInstance()
    {
        if (is_null(self::$instance)) {
            throw new \RuntimeException('Nucleus application kernel instance not created');
        }

        return self::$instance;
    }
}
