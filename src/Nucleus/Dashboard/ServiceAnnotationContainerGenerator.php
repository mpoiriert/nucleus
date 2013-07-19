<?php

namespace Nucleus\Dashboard;

use Nucleus\DependencyInjection\BaseAnnotationContainerGenerator;
use Nucleus\DependencyInjection\GenerationContext;

class ServiceAnnotationContainerGenerator extends BaseAnnotationContainerGenerator
{
    /**
     * 
     * @param GenerationContext $context
     * @param \Nucleus\IService\Dashboard\Service $annotation
     */
    public function generate(GenerationContext $context, $annotation)
    {
        $serviceName = $context->getServiceName();
        $dashboard = $context->getContainerBuilder()->getDefinition('dashboard');
        
        $dashboard->addMethodCall(
            'addService',
            array(
                $serviceName,
                $annotation->name
            )
        );
    }
}
