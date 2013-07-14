<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Description of GenerationContext
 *
 * @author Martin
 */
class GenerationContext
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    private $containerBuilder;
    private $serviceName;
    private $serviceDefinition;
    private $parsingNode;

    public function __construct(
    ContainerBuilder $containerBuilder, $serviceName, Definition $definition, $parsingNode
    )
    {
        $this->containerBuilder = $containerBuilder;
        $this->serviceName = $serviceName;
        $this->serviceDefinition = $definition;
        $this->parsingNode = $parsingNode;
    }

    /**
     * @return string class|method|property
     */
    public function getParsingContext()
    {
        return $this->parsingNode['context'];
    }

    /**
     * Return the name of the class|method|property
     * 
     * @return string
     */
    public function getParsingContextName()
    {
        return $this->parsingNode['contextName'];
    }
    
    public function getAnnotation()
    {
        return $this->parsingNode['annotation'];
    }

    /**
     * return Symfony\Component\DependencyInjection\ContainerBuilder;
     */
    public function getContainerBuilder()
    {
        return $this->containerBuilder;
    }

    /**
     * @return Definition
     */
    public function getServiceDefinition()
    {
        return $this->serviceDefinition;
    }

    /**
     * @return string
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }
}
