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
    const NUCLEUS_SERVICE_NAME = 'router';
    
    public function addRoute($name, $path, array $defaults = array(), array $requirements = array(), array $options = array(), $host = '', $schemes = array(), $methods = array());

    public function removeRoute($name);

    public function match($path, $host = '', $scheme = '', $method = '');

    public function generate($name, array $parameters = array());
}
