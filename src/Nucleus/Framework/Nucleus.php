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
use Nucleus\IService\EventDispatcher\IEventDispatcherService;

/**
 * Description of Nucleus
 *
 * @author Martin
 */
class Nucleus
{
    private $serviceContainer;

    public function __construct(DnaConfiguration $dna)
    {
        $this->serviceContainer = $this->loadServiceContainer($dna);
    }

    /**
     * @param DnaConfiguration $dna
     * 
     * @return \Nucleus\IService\DependencyInjection\IServiceContainer
     */
    protected function loadServiceContainer($dna)
    {
        $cachePath = $dna->freezeCachePath()->getCachePath();
        $class = 'ServiceContainer' . md5($cachePath);
        $file = $cachePath. '/serviceContainer/' . $class . '.php';
        
        $containerConfigCache = new ConfigCache($file, $dna->getDebug());
        $isNew = false;
        if (!class_exists($class)) {
            if (!$containerConfigCache->isFresh()) {
                $container = new ContainerBuilder();
                $nucleusCompilerPass = new NucleusCompilerPass($dna);
                $container->addCompilerPass($nucleusCompilerPass);
                $container->compile();
                $dumper = new PhpDumper($container);
                $containerConfigCache->write(
                    $dumper->dump(array('class' => $class, 'nucleus' => $nucleusCompilerPass->getConfiguration())), $container->getResources()
                );

                $isNew = true;
            }
            require($file);
            
        }
        $serviceContainer = new $class();
        /* @var $serviceContainer \Nucleus\DependencyInjection\BaseServiceContainer */
        $serviceContainer->initialize();
        
        if($isNew) {
            $serviceContainer->getServiceByName(IEventDispatcherService::NUCLEUS_SERVICE_NAME)
                ->dispatch(
                    'ServiceContainer.postDump',
                    $serviceContainer,
                    array('containerBuilder'=>$container,'dnaConfiguration'=>$dna)
                );
        }
        
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
     * @param DnaConfiguration $configurationFile
     * @return Nucleus
     */
    public static function factory($configuration)
    {
        if($configuration instanceof DnaConfiguration) {
            $dna = $configuration;
        } else {
            $dna = new DnaConfiguration();
            $dna->setConfiguration($configuration);
        }
        
        return new static($dna);
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
    public static function serviceFactory($dna, $serviceName)
    {
        $nucleus = self::factory($dna);
        return $nucleus->getServiceContainer()->getServiceByName($serviceName);
    }
}
