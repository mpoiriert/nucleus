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

        $cacheEntryName = $this->getCacheEntryName($invocation);
        
        $annotation = $this->getCacheableAnnotation($invocation);
        
        try {
            $result = $cacheService->get($cacheEntryName);
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
     * @param MethodInvocation $invocation
     * @return \Nucleus\IService\Cache\Cacheable
     */
    private function getCacheableAnnotation(MethodInvocation $invocation)
    {
        return $this->getAnnotation($invocation, 'Nucleus\IService\Cache\Cacheable');
    }
    
    private function getCacheEntryName(MethodInvocation $invocation)
    {
        return sprintf(
            '%s__%s__%s',
            get_class($invocation->getThis()),
            $invocation->getMethod()->getName(),
            serialize($invocation->getArguments())
        );
    }
}