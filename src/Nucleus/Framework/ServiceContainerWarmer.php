<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Framework;

use Nucleus\IService\DependencyInjection\IServiceContainer;
use Nucleus\IService\EventDispatcher\IEventDispatcherService;

/**
 * Description of ApplicationWarmer
 *
 * @author Martin
 */
class ServiceContainerWarmer
{
    /**
     * @var IServiceContainer 
     */
    private $serviceContainer;
    
    /**
     * @\Nucleus\IService\DependencyInjection\Inject
     * 
     * @param IServiceContainer $serviceContainer
     */
    public function __construct(IServiceContainer $service_container)
    {
        $this->serviceContainer = $service_container;
    }
    
    /**
     * Warm up the service container by loading all it's service and dispatch
     * a event so a hook can be done on it.
     * 
     * @\Nucleus\IService\CommandLine\Consolable(name="application:warmUp")
     */
    public function warmUp()
    {
        $eventDispatcher = $this->serviceContainer->getServiceByName(IEventDispatcherService::NUCLEUS_SERVICE_NAME);
        foreach($this->serviceContainer->getServiceNames() as $name) {
            $service = $this->serviceContainer->getServiceByName($name);
            $eventDispatcher->dispatch('Service.' . strtolower($name) . '.warmUp',$service);
        }

        $eventDispatcher->dispatch('ServiceContainer.warmUp',$this->serviceContainer);
    }
}
