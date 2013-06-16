<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\EventDispatcher;

use Nucleus\DependencyInjection\IServiceContainerGeneratorAnnotation;
use Nucleus\IService\EventDispatcher\IEventDispatcherService;
use Nucleus\DependencyInjection\GenerationContext;

/**
 * Description of Inject
 *
 * @Annotation
 */
class Listen implements IServiceContainerGeneratorAnnotation
{
    /**
     * @var string
     */
    private $eventName;

    /**
     * @var int
     */
    private $priority;

    public function __construct($values)
    {
        $this->eventName = isset($values['value']) ? $values['value'] : $values['eventName'];
        $this->priority = isset($values['priority']) ? $values['priority'] : 0;
    }

    public function getEventName()
    {
        return $this->eventName;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function processContainerBuilder(GenerationContext $context)
    {
        $serviceName = $context->getServiceName();

        $context->getContainerBuilder()
            ->getDefinition(IEventDispatcherService::NUCLEUS_SERVICE_NAME)
            ->addCodeInitialization('
  $service->addListener(
    "' . $this->getEventName() . '",
    function(\Nucleus\IService\EventDispatcher\IEvent $event) use ($serviceContainer) {
      $listener = array($serviceContainer->getServiceByName("' . $serviceName . '"),"' . $context->getParsingContextName() . '");
      $serviceContainer->getServiceByName(\Nucleus\IService\Invoker\IInvokerService::NUCLEUS_SERVICE_NAME)
        ->invoke($listener,$event->getParameters(),array($event, $event->getSubject()));
    },
    "' . $this->getPriority() . '"
  );
');
    }
}