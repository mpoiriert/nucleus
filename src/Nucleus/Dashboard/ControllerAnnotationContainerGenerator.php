<?php

namespace Nucleus\Dashboard;

use Nucleus\DependencyInjection\BaseAnnotationContainerGenerator;
use Nucleus\DependencyInjection\GenerationContext;

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
        
        $dashboard->addMethodCall(
            'addServiceAsController',
            array($serviceName)
        );
    }
}
