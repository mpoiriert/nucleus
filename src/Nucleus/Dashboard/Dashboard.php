<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Dashboard;

use Nucleus\Routing\Router;

/**
 * Description of Dashboard
 *
 * @author Martin
 */
class Dashboard
{
    /**
     * @var \Nucleus\Routing\Router 
     */
    private $routing;

    /**
     * @param \Nucleus\Routing\Router $routing
     * 
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function initialize(Router $routing)
    {
        $this->routing = $routing;
    }

    /**
     * @\Nucleus\IService\FrontController\ViewDefinition(template="nucleus/dashboard/home.twig")
     * 
     * @Route(name="dashboard", path="/nucleus/dashboard")
     */
    public function home()
    {
        return $this->routing->generate("dashboardLoad");
    }

    /**
     * @Route(name="dashboardLoad", path="/nucleus/dashboard/load")
     */
    public function load()
    {
        
    }
}
