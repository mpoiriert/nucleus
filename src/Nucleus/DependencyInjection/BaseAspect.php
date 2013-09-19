<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\DependencyInjection;

use Nucleus\IService\DependencyInjection\IServiceContainer;
use LogicException;
use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;

/**
 * Description of BaseAspect
 *
 * @author Martin
 * 
 * @\Nucleus\IService\DependencyInjection\Tag(name="autoStart")
 * @\Nucleus\IService\DependencyInjection\Tag(name="aspect")
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
    
    protected function getAnnotations(MethodInvocation $invocation, $annotationName = null)
    {
       $annotations = $this->getServiceContainer()
            ->getServiceByName('aspectKernel')
            ->getContainer()
            ->get('aspect.annotation.reader')
            ->getMethodAnnotations($invocation->getMethod()); 
       
       if(!$annotationName) {
           return $annotations;
       }
       
       return array_filter($annotations, function($annotation) use ($annotationName) {
          return $annotation instanceof $annotationName;
       });
    }


    protected function getAnnotation(MethodInvocation $invocation, $annotationName)
    {
        return $this->getServiceContainer()
            ->getServiceByName('aspectKernel')
            ->getContainer()
            ->get('aspect.annotation.reader')
            ->getMethodAnnotation($invocation->getMethod(), $annotationName);
    }
}
