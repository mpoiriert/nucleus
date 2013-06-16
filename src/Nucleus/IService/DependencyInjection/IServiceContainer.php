<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\DependencyInjection;

/**
 *
 * @author Martin
 */
interface IServiceContainer
{

    public function getServiceByName($name);

    public function getServicesByTag($tag);

    public function getServiceNames();

    public function getServiceConfiguration($name);

    /**
     * Will shutdown the service container and all the
     * service that are ILifeCycleAware
     */
    public function shutdown();
}