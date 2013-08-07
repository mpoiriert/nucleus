<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\DependencyInjection;

use Nucleus\IService\DependencyInjection\IServiceContainer;

/**
 * Description of IServiceContainerAware
 *
 * @author Martin
 */
interface IServiceContainerAware
{
    /**
     * @param IServiceContainer $serviceContainer
     * 
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function setServiceContainer(IServiceContainer $serviceContainer);
}
