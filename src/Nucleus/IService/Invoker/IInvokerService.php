<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Invoker;

/**
 *
 * @author Martin
 */
interface IInvokerService
{
    /**
     * The service name use as a reference
     */
    const NUCLEUS_SERVICE_NAME = 'invoker';

    /**
     * Method to call the $callable by trying to find the best parameters pass
     * to the invoke function.
     * 
     * @param mixed $callable
     * @param array $namedParameters
     * @param array $typedParameters
     * 
     * @return mixed
     */
    public function invoke($callable, array $namedParameters = array(), array $typedParameters = array());
}
