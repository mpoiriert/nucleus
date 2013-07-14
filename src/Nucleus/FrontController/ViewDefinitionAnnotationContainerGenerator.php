<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\FrontController;

use Nucleus\DependencyInjection\BaseAnnotationContainerGenerator;
use Nucleus\DependencyInjection\GenerationContext;

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
     * @param \Nucleus\IService\FrontController\ViewDefinition $annotation
     */
    public function generate(GenerationContext $context, $annotation)
    {
        $serviceName = $context->getServiceName();
        $methodName = $context->getParsingContextName();
        $controllerViewConciliatorDefinition = $context->getContainerBuilder()
            ->getDefinition('controllerViewConciliator');
        
        $controllerViewConciliatorDefinition->addMethodCall(
            'setViewDefinition',
            array(
                $serviceName . '/' . $methodName,
                $annotation->name,
                $annotation->template,
                $annotation->variables
            )
        );
    }
}
