<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\View;

use Nucleus\DependencyInjection\BaseAnnotationContainerGenerator;
use Nucleus\DependencyInjection\GenerationContext;
use Nucleus\IService\View\IViewConciliator;

/**
 * Description of ViewDefinitionAnnotationContainerGenerator
 *
 * @author Martin
 */
class ViewDefinitionAnnotationContainerGenerator extends BaseAnnotationContainerGenerator
{
    /**
     * 
     * @param GenerationContext $context
     * @param \Nucleus\IService\View\IViewDefinition $annotation
     */
    public function generate(GenerationContext $context, $annotation)
    {
        $serviceName = $context->getServiceName();
        $methodName = $context->getParsingContextName();
        
        $definition = $context->getContainerBuilder()->getDefinition(IViewConciliator::NUCLEUS_SERVICE_NAME);
        
    }
}
