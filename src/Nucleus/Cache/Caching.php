<?php 

namespace Nucleus\Cache;

use Go\Aop\Aspect;
use Go\Aop\Intercept\FieldAccess;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Annotation\After;
use Go\Lang\Annotation\Before;
use Go\Lang\Annotation\Around;
use Go\Lang\Annotation\Pointcut;
use Nucleus\IService\Cache\ICacheService;

/**
 * Monitor aspect
 * 
 * @Annotation
 */
class Caching implements Aspect
{
    /**
     *
     * @var ICacheService 
     */
    private $cache;
    
    /**
     * @param ICacheService $cache
     * 
     * @Inject
     */
    public function setCache(ICacheService $cache)
    {
        $this->cache = $cache;
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
        return $invocation->proceed();
        
        static $memoryCache = array();

        $time  = microtime(true);

        $obj   = $invocation->getThis();
        $class = is_object($obj) ? get_class($obj) : $obj;
        $key   = $class . ':' . $invocation->getMethod()->name;
        if (!isset($memoryCache[$key])) {
            $memoryCache[$key] = $invocation->proceed();
        }

        echo "Take ", sprintf("%0.3f", (microtime(true) - $time) * 1e3), "ms to call method<br>", PHP_EOL;
        return $memoryCache[$key];
    }
}