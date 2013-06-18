<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Routing;

use Nucleus\DependencyInjection\IServiceContainerGeneratorAnnotation;
use Nucleus\DependencyInjection\GenerationContext;
use InvalidArgumentException;

/**
 * Description of Route
 * 
 * @author Martin
 * 
 * @Annotation
 */
class I18nRoute extends Route implements IServiceContainerGeneratorAnnotation
{
    /**
     * @var Route 
     */
    private $routes = array();
    
    public function setRoutes($routes)
    {
        $this->routes = $routes;
    }
    
    public function processContainerBuilder(GenerationContext $context)
    {
        parent::processContainerBuilder($context);
        foreach($this->routes as $culture => $route) {
            foreach(array('Name','Defaults','Requirements','Options','Host','Schemes','Methods') as $method) {
                $getter = 'get' . $method;
                if($route->{$getter}()) {
                    throw new InvalidArgumentException('Route [' . $this->getName() . '] for culture [' . $culture . '] cannot have a value for [' . $method . ']');
                }
            }
            $route->setName($this->getName());
            $defaults = $this->getDefaults();
            $defaults['_culture'] = $culture;
            $route->setDefaults($defaults);
            
            $route->processContainerBuilder($context);
        }
    }
}
