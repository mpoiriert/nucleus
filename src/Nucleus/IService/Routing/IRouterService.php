<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Routing;

use Symfony\Component\HttpFoundation\Request;

/**
 * Description of Router
 *
 * @author Martin
 */
interface IRouterService
{
    const NUCLEUS_SERVICE_NAME = 'routing';
    
    /**
     * Generates an absolute URL, e.g. "http://example.com/dir/file".
     */
    const ABSOLUTE_URL = true;

    /**
     * Generates an absolute path, e.g. "/dir/file".
     */
    const ABSOLUTE_PATH = false;

    /**
     * Generates a relative path based on the current request path, e.g. "../parent-file".
     * @see UrlGenerator::getRelativePath()
     */
    const RELATIVE_PATH = 'relative';

    /**
     * Generates a network path, e.g. "//example.com/dir/file".
     * Such reference reuses the current scheme but specifies the host.
     */
    const NETWORK_PATH = 'network';
    
    public function addRoute($name, $path, array $defaults = array(), array $requirements = array(), array $options = array(), $host = '', $schemes = array(), $methods = array());

    public function removeRoute($name);

    public function match($path, $host = '', $scheme = '', $method = '');

    public function generate($name, array $parameters = array(), $referenceType = self::ABSOLUTE_PATH);
    
    public function setCurrentRequest(Request $request);
    
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
