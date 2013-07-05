<?php 

namespace Nucleus\Cache;

;
use Go\Aop\Intercept\FieldAccess;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\After;
use Go\Lang\Annotation\Before;
use Go\Lang\Annotation\Around;
use Go\Lang\Annotation\Pointcut;
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
     * @Around("execution(public **->handleRequest(*))")
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