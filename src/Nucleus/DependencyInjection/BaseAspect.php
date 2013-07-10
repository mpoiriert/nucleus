<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\DependencyInjection;

use Nucleus\IService\DependencyInjection\IServiceContainer;
use LogicException;
use Go\Aop\Aspect;

/**
 * Description of BaseAspect
 *
 * @author Martin
 * 
 * @Tag("autoStart")
 * @Tag("aspect")
 */
class BaseAspect implements IServiceContainerAware, Aspect
{
    /**
     * @var IServiceContainer 
     */
    private $serviceContainer;
    
    /**
     * @param IServiceContainer $serviceContainer
     */
    public function setServiceContainer(IServiceContainer $serviceContainer)
    {
        $this->serviceContainer = $serviceContainer;
    }
    
    /**
     * @return IServiceContainer
     * @throws LogicException
     */
    protected function getServiceContainer()
    {
        if(is_null($this->serviceContainer)) {
            throw new LogicException('Service container not set.');
        }
        
        return $this->serviceContainer;
    }
    
    protected function getAnnotation(MethodInvocation $invocation, $annotation)
    {
        return $this->getServiceContainer()
            ->getServiceByName('aspectKernel')
            ->getContainer()
            ->get('aspect.annotation.reader')
            ->getMethodAnnotation($invocation->getMethod(), $annotation);
    }
}
