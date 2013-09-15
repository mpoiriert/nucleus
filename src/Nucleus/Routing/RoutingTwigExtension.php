<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Routing;

use Twig_Extension;
use Twig_SimpleFunction;
use Nucleus\IService\Routing\IRouterService;

/**
 * Description of RoutingTwigExtention
 *
 * @author Martin
 * 
 * @\Nucleus\IService\DependencyInjection\Tag(name="twigRenderer.twigExtension")
 */
class RoutingTwigExtension extends Twig_Extension
{
    /**
     * @var IRouterService
     */
    private $routing;
    
    /**
     * @\Nucleus\IService\DependencyInjection\Inject
     * 
     * @param IRouterService $routing
     */
    public function __construct(IRouterService $routing) 
    {
        $this->routing = $routing;
    }
    
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction(
                'nucleus_current_i18n_route', array($this, 'routeTranslation'), array('is_safe' => array('html'))
            )
        );
    }

    public function routeTranslation($culture,  $referenceType = self::ABSOLUTE_PATH, $scheme=null)
    {
        return $this->routing->generateI18nRouteFromCurrentRequest($culture, $referenceType, $scheme);
    }

    public function getName()
    {
        return "nuleus_routing";
    }
}
