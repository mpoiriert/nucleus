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
use InvalidArgumentException;
use Nucleus\IService\Routing\IRouterService;
use Symfony\Component\HttpFoundation\Request;
use ArrayObject;
use Nucleus\IService\EventDispatcher\IEventDispatcherService;
use Nucleus\IService\Routing\NoHostFoundForCultureException;
/**
 * Description of Router
 *
 * @author Martin
 */
class Router implements IRouterService
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
    
    /**
     * @var IEventDispatcherService
     */
    private $eventDispatcher;
    
    private $defaultParameters = array();
    
    private $cultureHosts = array();
    
    /**
     * @\Nucleus\IService\ApplicationContext\BoundToSession
     */
    private $sessionDefaultParameters = array();

    private $currentRequest;

    /**
     * @param RequestContext $routingRequestContext
     * 
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function __construct(IEventDispatcherService $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->routeCollection = new RouteCollection();
        $this->context = new RequestContext();
        $this->urlMatcher = new UrlMatcher($this->routeCollection, $this->context);
        $this->urlGenerator = new UrlGenerator($this->routeCollection, $this->context);
    }
    
    public function setHostForCulture($host, $culture = 'default')
    {
        $this->cultureHosts[$this->normalizeCulture($culture)] = $host;
    }
    
    private function normalizeCulture($culture) 
    {
        return strtolower(str_replace('_', '-', $culture));
    }
    
    /**
     * @param string $culture
     * @return string
     * @throws NoHostFoundForCultureException
     * @throws \InvalidArgumentException
     */
    public function getHostForCulture($culture = 'default')
    {
        $culture = $this->normalizeCulture($culture);
        if(array_key_exists($culture, $this->cultureHosts)) {
            return $this->cultureHosts[$culture];
        }
        
        if($culture == 'default') {
            throw new NoHostFoundForCultureException(NoHostFoundForCultureException::formatMessage($culture));
        }
       
        try {
          return $this->getHostForCulture(get_parent_culture($culture));
        } catch (NoHostFoundForCultureException $e) {
            throw new NoHostFoundForCultureException(NoHostFoundForCultureException::formatMessage($culture));
        }
        
    }
    
    /**
     * @\Nucleus\IService\EventDispatcher\Listen("Culture.change")
     * 
     * @param string $culture
     */
    public function setDefaultCulture($culture)
    {
        $this->setDefaultParameter('_culture', $culture);
    }

    public function addRoute($name, $path, array $defaults = array(), array $requirements = array(), array $options = array(), $host = '', $schemes = array(), $methods = array())
    {
        if(isset($defaults['_culture'])) {
            $name = $this->getI18nRouteName($name, $defaults['_culture']);
        }
        $route = new SymfonyRoute($path, $defaults, $requirements, $options, $host, $schemes, $methods);
        $this->routeCollection->add($name, $route);
    }

    public function removeRoute($name)
    {
        $this->routeCollection->remove($name);
    }

    public function match($path, $host = '', $scheme = '', $method = '')
    {
        $context = $this->urlMatcher->getContext();
        $newContext = clone $context;
        $newContext->setHost($host);
        $newContext->setScheme($scheme);
        $newContext->setMethod($method);
        $this->urlMatcher->setContext($newContext);
        try {
            $result = $this->urlMatcher->match($path);
            $this->urlMatcher->setContext($context);
            return $result;
        } catch(\Exception $e) {
            $this->urlMatcher->setContext($context);
            throw $e;
        }
    }

    public function generate($name, array $parameters = array(), $referenceType = self::ABSOLUTE_PATH, $scheme = null)
    {
        $parameters = array_deep_merge(
            $this->sessionDefaultParameters, $this->defaultParameters, $parameters
        );

        if ($scheme) {
            $oldScheme = $this->context->getScheme();
            $this->context->setScheme($scheme);
        }

        $cultures = $this->getCultures($parameters);

        $route = null;
        $oldHost = $this->context->getHost();
        try {
            foreach ($cultures as $culture) {
                $routeName = $this->getI18nRouteName($name, $culture);
                if ($this->routeCollection->get($routeName)) {
                    try {
                        $host = $this->getHostForCulture($culture == '' ? 'default' : $culture);
                        $this->context->setHost($host);
                        if($host != $oldHost) {
                            $referenceType = self::ABSOLUTE_URL;
                        }
                    } catch(NoHostFoundForCultureException $e) {
                        //We do nothing with the exception, it will use the request domain
                    }
                    $route = $this->urlGenerator->generate($routeName, $parameters, $referenceType);
                    break;
                }
            }

            if (is_null($route)) {
                $route = $this->urlGenerator->generate($name, $parameters);
            }
        } catch (\Exception $e) {
            //We want to put the old scheme back on exception
            if ($scheme) {
                $this->context->setScheme($oldScheme);
            }
            $this->context->setHost($oldHost);
            throw $e;
        }
        
        $this->context->setHost($oldHost);
        if ($scheme) {
            $this->context->setScheme($oldScheme);
        }

        return $route;
    }
    
    private function getCultures(array $parameters)
    {
        if(!isset($parameters['_culture'])) {
            $culture = '';
        } else {
            $culture = $parameters['_culture'];
        }

        $culture = $this->normalizeCulture($culture);
        
        switch(strlen($culture)) {
            case 0:
                return array('');
            case 2:
                return array($culture,'');
            case 5:
                return array($culture, substr($culture,0,2),'');
        }

        throw new InvalidArgumentException('The culture [' . $culture . '] does not have a valid format');
    }
    
    private function getI18nRouteName($name, $culture)
    {
        if(!$culture) {
            return $name;
        }
        return $culture . ':i18n:' . $name;
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

    public function setDefaultParameter($name, $value, $boundToSession = true)
    {
        if(is_null($value)) {
            unset($this->defaultParameters[$name]);
            unset($this->sessionDefaultParameters[$name]);
            return;
        }
        
        if($boundToSession) {
            unset($this->defaultParameters[$name]);
            $this->sessionDefaultParameters[$name] = $value;
        } else {
            unset($this->sessionDefaultParameters[$name]);
            $this->defaultParameters[$name] = $value;
        }
    }

    /**
     * @param Request $request
     */
    public function setCurrentRequest(Request $request)
    {
        $this->currentRequest = $request;
        $this->context->fromRequest($request);
    }

    public function getCurrentRequest()
    {
        return $this->currentRequest;
    }

    public function generateI18nRouteFromCurrentRequest($culture, $referenceType = self::ABSOLUTE_PATH, $scheme = null)
    {
        $result = $this->match(
            $this->context->getPathInfo(),
            $this->context->getHost(),
            $this->context->getScheme(),
            $this->context->getMethod()
        );
          
        $route = $result['_route'];
        
        if(strpos($route, ':i18n:') != false) {
            list(,,$route) = explode(':',$route,3);
        }
        
        $result['_culture'] = $culture;
        unset($result['_route']);
        $parameters = new ArrayObject($result);
        $this->eventDispatcher->dispatch(
            'Routing.preGenrateI18nRouteFromCurrentRequest',
            $this,
            compact('culture','referenceType','scheme','parameters')
        );
        return $this->generate($route, $parameters->getArrayCopy(), $referenceType, $scheme);
    }
}
