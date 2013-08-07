<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\DependencyInjection;

use Nucleus\IService\DependencyInjection\Inject;
use Symfony\Component\DependencyInjection\Variable;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description of InjectAnnotationContainerGenerator
 *
 * @author Martin
 */
class InjectAnnotationContainerGenerator implements IAnnotationContainerGenerator
{
    public function processContainerBuilder(GenerationContext $context)
    {
        $method = $context->getParsingContextName();
        $definition = $context->getServiceDefinition();
        $parameters = $this->getParameters(
            $definition->getClass(), 
            $method, 
            $context->getServiceName(),
            $context->getAnnotation()
        );
        
        if ($method == "__construct") {
            $definition->setArguments($parameters);
        } else {
            $definition->addMethodCall($method, $parameters);
        }
    }
    
    private function getParameters($class, $method, $serviceCurrentlyGenerated, Inject $annotation)
    {
        $reflectionMethod = new \ReflectionMethod($class, $method);

        $mapping = $annotation->getMapping();
        $parameters = array();
        foreach ($reflectionMethod->getParameters() as $parameter) {
            /* @var  $parameter \ReflectionParameter */
            if (array_key_exists($parameter->getName(), $mapping)) {
                $serviceName = $mapping[$parameter->getName()];
            } else {
                $serviceName = $parameter->getName();
            }

            switch (true) {
                case strpos($serviceName, '@') === 0:
                    $parameters[$parameter->getPosition()] = new Variable('this->getServicesByTag("' . substr($serviceName, 1) . '")');
                    break;
                case strpos($serviceName, '$') === 0:
                    if ($serviceName == '$') {
                        $configuration = '[' . $serviceCurrentlyGenerated . ']';
                    } else {
                        $configuration = substr($serviceName, 1);
                    }
                    $parameters[$parameter->getPosition()] = new Variable('this->getServiceByName("configuration")->get("' . $configuration . '")');
                    break;
                default:
                    $parameters[$parameter->getPosition()] = new Reference(
                        $serviceName,
                        $parameter->allowsNull() ? ContainerInterface::NULL_ON_INVALID_REFERENCE : ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE
                    );
                    break;
            }
        }
        return $parameters;
    }

    
}
