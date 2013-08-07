<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\EventDispatcher;

use Nucleus\DependencyInjection\IAnnotationContainerGenerator;
use Nucleus\IService\EventDispatcher\IEventDispatcherService;
use Nucleus\DependencyInjection\GenerationContext;

/**
 * Description of ListenAnnotationContainerGenerator
 *
 * @author Martin
 */
class ListenAnnotationContainerGenerator implements IAnnotationContainerGenerator
{
    /**
     * @param GenerationContext $context
     */
     public function processContainerBuilder(GenerationContext $context)
    {
        $serviceName = $context->getServiceName();
        $annotation = $context->getAnnotation();
        /* @var $annotation \Nucleus\IService\EventDispatcher\Listen */
        
        if(is_null($annotation->eventName)) {
            throw new \Exception('Attribute value [eventName] for annotation [Listen] is required');
        }
        
        $context->getContainerBuilder()
            ->getDefinition(IEventDispatcherService::NUCLEUS_SERVICE_NAME)
            ->addCodeInitialization('
  $service->addListener(
    "' . $annotation->eventName . '",
    function(\Nucleus\IService\EventDispatcher\IEvent $event) use ($serviceContainer) {
      $listener = array($serviceContainer->getServiceByName("' . $serviceName . '"),"' . $context->getParsingContextName() . '");
      $serviceContainer->getServiceByName(\Nucleus\IService\Invoker\IInvokerService::NUCLEUS_SERVICE_NAME)
        ->invoke($listener,$event->getParameters(),array($event, $event->getSubject()));
    },
    "' . $annotation->priority . '"
  );
');
    }
}
