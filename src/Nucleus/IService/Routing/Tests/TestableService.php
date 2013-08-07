<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Nucleus\IService\Routing\Tests;


/**
 * This class must be in it's own file since Aspect will not be parse prior to
 * the loading of the class
 * 
 * @see RouteAnnotationParsingTest
 */
class TestableService
{
    /**
     * @\Nucleus\IService\Routing\Route(name="test",path="/test",defaults={"test" = 0})
     * @\Nucleus\IService\Routing\I18nRoute(
     *      name="test-i18n",path="/test-i18n",defaults={"test" = 0},
     *      routes={
     *          "en-us" = @\Nucleus\IService\Routing\Route(path="/test-en-us"),
     *          "en-fr" = @\Nucleus\IService\Routing\Route(path="/test-fr-fr")
     *      }
     * )
     */
    public function route()
    {
        
    }
}
