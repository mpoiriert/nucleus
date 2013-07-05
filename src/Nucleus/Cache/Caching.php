<?php 

namespace Nucleus\Cache;

use Go\Aop\Intercept\MethodInvocation;
use Nucleus\IService\Cache\ICacheService;
use Nucleus\DependencyInjection\BaseAspect;
use Nucleus\IService\Cache\EntryNotFoundException;

/**
 * Monitor aspect
 * 
 * @Annotation
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
     * @Go\Lang\Annotation\Around("@annotation(Nucleus\Cache\Caching)")
     */
    public function aroundCacheable(MethodInvocation $invocation)
    {
        $cacheService = $this->getCacheService();

        $cacheEntryName = $this->getCacheEntryName($invocation);
        
        try {
            $result = $cacheService->get($cacheEntryName);
        } catch (EntryNotFoundException $e) {
            $result = $invocation->proceed();
            $cacheService->set($cacheEntryName, $result);
        }
        return $result;
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