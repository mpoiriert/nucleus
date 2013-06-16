<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\ObjectFactory;

/**
 *
 * @author Martin
 */
interface IObjectFactoryService
{
    /**
     * The service name use as a reference
     */
    const NUCLEUS_SERVICE_NAME = 'objectFactory';

    /**
     * @return mixed 
     */
    public function createObject($class, array $constructorArguments = array());

    /**
     * @param \Nucleus\IService\ObjectFactory\IObjectBuilder $objectBuilder
     */
    public function registerObjectBuilder(IObjectBuilder $builder);
}
