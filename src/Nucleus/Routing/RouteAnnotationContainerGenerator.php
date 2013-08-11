<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Routing;

use Nucleus\DependencyInjection\IAnnotationContainerGenerator;
use Nucleus\DependencyInjection\GenerationContext;
use Nucleus\IService\Routing\Route;
use Nucleus\IService\Routing\I18nRoute;

/**
 * Description of RouteAnnotationContainerGenerator
 *
 * @author Martin
 */
class RouteAnnotationContainerGenerator implements IAnnotationContainerGenerator
{

    /**
     * @param GenerationContext $context
     */
    public function processContainerBuilder(GenerationContext $context)
    {
        $annotation = $context->getAnnotation();
        
        $this->addRoute($annotation, $context);
        
        if(!($annotation instanceof I18nRoute) ) {
            return;
        }

        /* @var $annotation \Nucleus\IService\Routing\I18nRoute */
        foreach($annotation->getRoutes() as $culture => $route) {
            foreach(array('Name','Defaults','Requirements','Options','Host','Schemes','Methods') as $method) {
                $getter = 'get' . $method;
                if($route->{$getter}()) {
                    throw new InvalidArgumentException('Route [' . $this->getName() . '] for culture [' . $culture . '] cannot have a value for [' . $method . ']');
                }
            }
            $route->setName($annotation->getName());
            $defaults = $annotation->getDefaults();
            $defaults['_culture'] = $culture;
            $route->setDefaults($defaults);
            
            $this->addRoute($route, $context);
        }   
    }
    
    /**
     * This method is use by the I18nRouteAnnotationParser
     * 
     * @param \Nucleus\IService\Routing\Route $route
     * @param \Nucleus\DependencyInjection\GenerationContext $context
     */
    private function addRoute(Route $route, GenerationContext $context)
    {
        //We don't add route with no name
        if(!$route->getName()) {
            return;
        }
        
        $defaults = $route->getDefaults();
        $defaults['_service'] = array(
            'name' => $context->getServiceName(),
            'method' => $context->getParsingContextName()
        );
        
        $arguments = array(
            $route->getName(),
            $route->getPath(),
            $defaults,
            $route->getRequirements(),
            $route->getOptions(),
            $route->getHost(),
            $route->getSchemes(),
            $route->getMethods()
        );

        $context->getContainerBuilder()
            ->getDefinition("routing")
            ->addMethodCall('addRoute', $arguments);
    }
}
