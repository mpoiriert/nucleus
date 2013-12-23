<?php

namespace Nucleus\Dashboard;

use Nucleus\DependencyInjection\BaseAnnotationContainerGenerator;
use Nucleus\DependencyInjection\GenerationContext;

/**
 * Registers a controller marked with a @Controller annotation in the dashboard
 */
class ControllerAnnotationContainerGenerator extends BaseAnnotationContainerGenerator
{
    /**
     * 
     * @param GenerationContext $context
     * @param \Nucleus\IService\Dashboard\Controller $annotation
     */
    public function generate(GenerationContext $context, $annotation)
    {
        $serviceName = $context->getServiceName();
        $dashboard = $context->getContainerBuilder()->getDefinition('dashboard');

        if (!($name = $annotation->name)) {
            $def = $context->getContainerBuilder()->getDefinition($serviceName);
            $classname = $def->getClass();
            $name = substr($classname, strrpos($classname, '\\') + 1);
        }
        
        $dashboard->addMethodCall(
            'addServiceAsController',
            array($serviceName, $name)
        );
    }
}
