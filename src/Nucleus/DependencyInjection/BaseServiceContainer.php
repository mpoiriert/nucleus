<?php

namespace Nucleus\DependencyInjection;

use Nucleus\Framework\Nucleus;
use Nucleus\IService\DependencyInjection\ILifeCycleAware;
use Nucleus\IService\DependencyInjection\IServiceContainer;
use Nucleus\IService\DependencyInjection\ServiceDisabledException;
use Nucleus\IService\DependencyInjection\ServiceDoesNotExistsException;
use Symfony\Component\DependencyInjection\Container;
use Go\Aop\Aspect;

abstract class BaseServiceContainer extends Container implements IServiceContainer
{
    protected $tags = array();
    protected $disabled = array();
    protected $serviceConfigurations = array();
    
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
        $this->getServicesByTag("autoStart");
        register_shutdown_function(array($this, 'shutdown'));
    }

    public function shutdown()
    {
        foreach ($this->startedServices as $service) {
            $service->serviceShutdown();
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

        $service = parent::get($name);
        
        if ($service instanceof ILifeCycleAware && !in_array($service, $this->startedServices)) {
            $this->startedServices[spl_object_hash($service)] = $service;
            $service->serviceStart();
        }

        $this->loadAspect($service);
        
        return $service;
    }
    
    private function loadAspect($service)
    {
        if ($service instanceof Aspect && !in_array($service, $this->loadedAspects)) {
            $this->loadedAspects[spl_object_hash($service)] = $service;
            $this->getServiceContainer()->registerAspect($service);
        }
    }
    
    private function getServiceContainer()
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

        return isset($this->serviceConfigurations[$name]) ? $this->serviceConfigurations[$name] : null;
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
