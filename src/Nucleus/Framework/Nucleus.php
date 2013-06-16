<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Framework;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Nucleus\DependencyInjection\NucleusCompilerPass;
use Nucleus\DependencyInjection\PhpDumper;
use Symfony\Component\Config\ConfigCache;

/**
 * Description of Nucleus
 *
 * @author Martin
 */
class Nucleus
{
    private $serviceContainer;

    public function __construct($configurationFile)
    {
        $this->serviceContainer = $this->loadServiceContainer($configurationFile);
    }

    /**
     * @param type $configuration
     * 
     * @return \Nucleus\IService\DependencyInjection\IServiceContainer
     */
    protected function loadServiceContainer($configurationFile)
    {
        $escaping = md5(serialize($configurationFile));
        $class = 'ServiceContainer' . $escaping;
        $file = __DIR__ . '/../../../cache/' . $escaping . '/' . $class . '.php';
        $containerConfigCache = new ConfigCache($file, true);
        if (!class_exists($class)) {
            if (!$containerConfigCache->isFresh()) {
                $container = new ContainerBuilder();
                $nucleusCompilerPass = new NucleusCompilerPass($configurationFile);
                $container->addCompilerPass($nucleusCompilerPass);
                $container->compile();
                $dumper = new PhpDumper($container);
                $containerConfigCache->write(
                    $dumper->dump(array('class' => $class, 'nucleus' => $nucleusCompilerPass->getConfiguration())), $container->getResources()
                );
            }
            require($file);
        }
        $serviceContainer = new $class();
        $serviceContainer->initialize();
        return $serviceContainer;
    }

    /**
     * 
     * @return \Nucleus\IService\DependencyInjection\IServiceContainer
     */
    public function getServiceContainer()
    {
        return $this->serviceContainer;
    }

    /**
     * 
     * @param type $configurationFile
     * @return Nucleus
     */
    public static function factory($configurationFile)
    {
        return new static($configurationFile);
    }

    /**
     * This is a method to use to initialize a stand alone service. You should
     * use a new Nucleus application if you want to access the service container
     * that have been genenerated since the reference will be "lost" with
     * this method.
     * 
     * @param mixed $configuration
     * @param string $serviceName
     * @return mixed
     */
    public static function serviceFactory($configuration, $serviceName)
    {
        $nucleus = new static($configuration);
        return $nucleus->getServiceContainer()->getServiceByName($serviceName);
    }
}
