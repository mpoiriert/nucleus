<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Routing;

use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Nucleus\Framework\Nucleus;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * Description of Router
 *
 * @author Martin
 */
class Router
{
    /**
     * @var RouteCollection
     */
    private $routeCollection;

    /**
     * @var RequestContext 
     */
    private $context;

    /**
     * @var UrlMatcher
     */
    private $urlMatcher;

    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    public function __construct()
    {
        $this->routeCollection = new RouteCollection();
        $this->context = new RequestContext();
        $this->urlMatcher = new UrlMatcher($this->routeCollection, $this->context);
        $this->urlGenerator = new UrlGenerator($this->routeCollection, $this->context);
    }

    public function addRoute($name, $path, array $defaults = array(), array $requirements = array(), array $options = array(), $host = '', $schemes = array(), $methods = array())
    {
        $route = new SymfonyRoute($path, $defaults, $requirements, $options, $host, $schemes, $methods);
        $this->routeCollection->add($name, $route);
    }

    public function removeRoute($name)
    {
        $this->routeCollection->remove($name);
    }

    public function match($pathinfo)
    {
        return $this->urlMatcher->match($pathinfo);
    }

    public function generate($name, array $parameters = array())
    {
        return $this->urlGenerator->generate($name, $parameters);
    }

    /**
     * @param mixed $configuration
     * @return Router
     */
    public static function factory($configuration = null)
    {
        if (is_null($configuration)) {
            $configuration = __DIR__ . '/nucleus.json';
        }

        return Nucleus::serviceFactory($configuration, 'routing');
    }
}
