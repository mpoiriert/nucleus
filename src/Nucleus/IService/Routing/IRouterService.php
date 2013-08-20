<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Routing;

/**
 * Description of Router
 *
 * @author Martin
 */
interface IRouterService
{
    const NUCLEUS_SERVICE_NAME = 'routing';
    
    public function addRoute($name, $path, array $defaults = array(), array $requirements = array(), array $options = array(), $host = '', $schemes = array(), $methods = array());

    public function removeRoute($name);

    public function match($path, $host = '', $scheme = '', $method = '');

    public function generate($name, array $parameters = array());
    
    /**
     * Set a default parameter that will be use for any route generation if not
     * available in the generate parameters array. Set null to remove it. If
     * the boundToSession is true the parameter will be kept for all call
     * not just current one.
     * 
     * @param string $name
     * @param mixed $value
     * @param boolean $boundToSession
     */
    public function setDefaultParameter($name, $value, $boundToSession = true);
}
