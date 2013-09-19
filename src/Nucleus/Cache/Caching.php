<?php 

namespace Nucleus\Cache;

use Go\Aop\Intercept\MethodInvocation;
use Nucleus\IService\Cache\ICacheService;
use Nucleus\DependencyInjection\BaseAspect;
use Nucleus\IService\Cache\EntryNotFoundException;

/**
 * Monitor aspect
 */
class Caching extends BaseAspect 
{
    private $clearNamespaceProcessed = array();
    
    /**
     * @return ICacheService
     */
    private function getCacheService()
    {
        return $this->getServiceContainer()->getServiceByName(ICacheService::NUCLEUS_SERVICE_NAME);
    }
    
    /**
     * Cacheable methods
     *
     * @param MethodInvocation $invocation Invocation
     *
     * @Go\Lang\Annotation\Around("@annotation(Nucleus\IService\Cache\Cacheable)")
     */
    public function aroundCacheable(MethodInvocation $invocation)
    {
        $cacheService = $this->getCacheService();

        $annotation = $this->getCacheableAnnotation($invocation);
        
        $cacheEntryName = $this->getCacheEntryName($invocation, $annotation);
        
        try {
            $result = $cacheService->get($cacheEntryName, $annotation->namespace);
        } catch (EntryNotFoundException $e) {
            $result = $invocation->proceed();
            $cacheService->set(
                $cacheEntryName, 
                $result, 
                $annotation->timeToLive, 
                $annotation->namespace
            );
        }
        return $result;
    }
    
    /**
     * Invalidate methods
     *
     * @param MethodInvocation $invocation Invocation
     *
     * @Go\Lang\Annotation\Around("@annotation(Nucleus\IService\Cache\Invalidate)")
     */
    public function aroundInvalidate(MethodInvocation $invocation)
    {
        $cacheService = $this->getCacheService();
         
        $annotation = $this->getInvalidateAnnotation($invocation);
        
        $result = $invocation->proceed();
        
        $cacheEntryName = $this->getCacheEntryName($invocation, $annotation);
        
        $cacheService->delete(
            $cacheEntryName, 
            $annotation->namespace
        );
        
        return $result;
    }
    
    /**
     * Invalidate methods
     *
     * @param MethodInvocation $invocation Invocation
     *
     * @Go\Lang\Annotation\After("@annotation(Nucleus\IService\Cache\ClearNamespace)")
     */
    public function afterClearNamespace(MethodInvocation $invocation)
    {
        $objectKey = spl_object_hash($invocation);
        if(in_array($objectKey, $this->clearNamespaceProcessed)) {
            return;
        }
        $cacheService = $this->getCacheService();
         
        foreach($this->getClearNamespaceAnnotations($invocation) as $annotation) {
            if(!$annotation->namespace) {
                continue;
            }
            $cacheService->clearNamespace($annotation->namespace);
        }
        
        $this->clearNamespaceProcessed[] = $objectKey;
    }
    
    /**
     * @param MethodInvocation $invocation
     * @return \Nucleus\IService\Cache\Cacheable
     */
    private function getCacheableAnnotation(MethodInvocation $invocation)
    {
        return $this->getAnnotation($invocation, 'Nucleus\IService\Cache\Cacheable');
    }
    
    /**
     * @param MethodInvocation $invocation
     * @return \Nucleus\IService\Cache\ClearNamespace[]
     */
    private function getClearNamespaceAnnotations(MethodInvocation $invocation)
    {
        return $this->getAnnotations($invocation, 'Nucleus\IService\Cache\ClearNamespace');
    }
    
    /**
     * @param MethodInvocation $invocation
     * @return \Nucleus\IService\Cache\Invalidate
     */
    private function getInvalidateAnnotation(MethodInvocation $invocation)
    {
        return $this->getAnnotation($invocation, 'Nucleus\IService\Cache\Invalidate');
    }
    
    private function getCacheEntryName(MethodInvocation $invocation, $annotation)
    {
        if(isset($annotation->keyName)) {
            return $this->getCacheEntryNameFromString($invocation, $annotation->keyName);
        }
        
        return sprintf(
            '%s__%s__%s',
            get_class($invocation->getThis()),
            $invocation->getMethod()->getName(),
            serialize($invocation->getArguments())
        );
    }
    
    private function getCacheEntryNameFromString(MethodInvocation $invocation, $name)
    {
        $arguments = $invocation->getArguments();
        $replaces = array();
        foreach($invocation->getMethod()->getParameters() as $parameter) {
            /* @var $parameter \ReflectionParameter */
            $replaces['$' . $parameter->getName()] = serialize($arguments[$parameter->getPosition()]);
        }
        return str_replace(
            array_keys($replaces), 
            array_values($replaces), 
            $name
        );
    }
}