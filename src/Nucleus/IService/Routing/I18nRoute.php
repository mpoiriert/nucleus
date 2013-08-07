<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Routing;

/**
 * @author Martin
 * 
 * @see Nucleus\IService\Routing\Tests\TestableService
 * 
 * @Annotation
 */
class I18nRoute extends Route
{
    /**
     * @var Route[]
     */
    private $routes = array();
    
    public function setRoutes($routes)
    {
        $this->routes = $routes;
    }
    
    /**
     * @return Route[]
     */
    public function getRoutes()
    {
        return $this->routes;
    }
}
