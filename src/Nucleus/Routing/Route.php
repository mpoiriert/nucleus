<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Routing;

use Symfony\Component\Routing\Annotation\Route as BaseRoute;
use Nucleus\DependencyInjection\IServiceContainerGeneratorAnnotation;
use Nucleus\DependencyInjection\GenerationContext;

/**
 * Description of Route
 * 
 * @author Martin
 * 
 * @Annotation
 */
class Route extends BaseRoute implements IServiceContainerGeneratorAnnotation
{

    public function processContainerBuilder(GenerationContext $context)
    {
        $defaults = $this->getDefaults();
        $defaults['_service'] = array(
            'name' => $context->getServiceName(),
            'method' => $context->getParsingContextName()
        );
        $arguments = array(
            $this->getName(),
            $this->getPath(),
            $defaults,
            $this->getRequirements(),
            $this->getOptions(),
            $this->getHost(),
            $this->getSchemes(),
            $this->getMethods()
        );

        $context->getContainerBuilder()
            ->getDefinition("routing")
            ->addMethodCall('addRoute', $arguments);
    }
}
