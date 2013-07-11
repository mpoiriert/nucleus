<?php

namespace Nucleus\DependencyInjection;

use Nucleus\Framework\Nucleus;
use Nucleus\IService\DependencyInjection\ILifeCycleAware;
use Nucleus\IService\DependencyInjection\IServiceContainer;
use Nucleus\IService\DependencyInjection\ServiceDisabledException;
use Nucleus\IService\DependencyInjection\ServiceDoesNotExistsException;
use Symfony\Component\DependencyInjection\Container;
use Go\Aop\Aspect;
use Nucleus\IService\EventDispatcher\IEventDispatcherService;

abstract class BaseServiceContainer extends Container implements IServiceContainer
{
    protected $tags = array();
    protected $disabled = array();
    
    //This is related to the alias array can be empty
    protected $aliases = array();
    
    /**
     * @var Aspect[]
     */
    private $loadedAspects = array();
    
    /**
     *
     * @var \Nucleus\IService\DependencyInjection\ILifeCycleAware[]
     */
    private $startedServices = array();

    public function initialize()
    {
        $this->getServicesByTag('aspect');
        $this->getServicesByTag("autoStart");
        register_shutdown_function(array($this, 'shutdown'));
    }
    
    public function shutdown()
    {
        foreach ($this->startedServices as $name => $service) {
            $this->getEventDispatcher()->dispatch('Service.preShutdown', $service);
            $this->getEventDispatcher()->dispatch('Service.' . $name . '.preShutdown', $service);
            if($service instanceof ILifeCycleAware) {
                $service->serviceShutdown();
            }
        }
    }

    protected function getThis()
    {
        return $this;
    }

    public function getServicesByTag($tag)
    {
        if (!isset($this->tags[$tag])) {
            return array();
        }

        $services = array();
        foreach ($this->tags[$tag] as $serviceName) {
            $services[] = $this->getServiceByName($serviceName);
        }

        return $services;
    }

    public function get($name, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE)
    {
        if (in_array($name, $this->disabled)) {
            throw new ServiceDisabledException("The service named [$name] is disabled.");
        }

        if (!$this->has($name)) {
            throw new ServiceDoesNotExistsException("The service named [$name] does not exists.");
        }

        $name = strtolower($name);

        // resolve aliases
        if (isset($this->aliases[$name])) {
            $name = $this->aliases[$name];
        }
        
        $service = parent::get($name);
        
        if (!in_array($service, $this->startedServices)) {
            $this->startedServices[$name] = $service;
            $this->getEventDispatcher()->dispatch('Service.postInitialized', $service);
            $this->getEventDispatcher()->dispatch('Service.' . $name . '.postInitialized', $service);
            if($service instanceof ILifeCycleAware) {
                $service->serviceStart();
            }
        }

        $this->loadAspect($service);
        
        return $service;
    }
    
    /**
     * @return IEventDispatcherService
     */
    private function getEventDispatcher()
    {
        return $this->getServiceByName(IEventDispatcherService::NUCLEUS_SERVICE_NAME);
    }
    
    private function loadAspect($service)
    {
        if ($service instanceof Aspect && !in_array($service, $this->loadedAspects)) {
            $aspectContainer = $this->getAspectContainer();
            try {
                $service = $aspectContainer->getAspect(get_class($service));
            } catch (\OutOfBoundsException $e) {
                $this->getAspectContainer()->registerAspect($service);
            }
   
            if($service instanceof IServiceContainerAware) {
                $service->setServiceContainer($this);
            }
            $this->loadedAspects[spl_object_hash($service)] = $service;
        }
    }
    
    /**
     * 
     * @return \Go\Core\AspectContainer
     */
    private function getAspectContainer()
    {
        return parent::get('aspectKernel')->getContainer();
    }

    public function getServiceByName($name)
    {
        return $this->get($name);
    }

    public function getServiceNames()
    {
        return $this->getServiceIds();
    }

    public function getServiceConfiguration($name)
    {
        if (!$this->has($name)) {
            throw new ServiceDoesNotExistsException("The service named [$name] does not exists.");
        }

        return $this->getServiceByName('configuration')->get('[' . $name . ']');
    }

    /**
     * @param mixed $configuration
     * @return IServiceContainer
     */
    public static function factory(array $configuration = null)
    {
        if (is_null($configuration)) {
            $configuration = __DIR__ . '/nucleus.json';
        }

        return Nucleus::serviceFactory($configuration, "serviceContainer");
    }
}
