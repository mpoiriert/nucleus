<?php 

namespace Nucleus\Security;

use Go\Aop\Intercept\MethodInvocation;
use Nucleus\IService\Security\IAccessControlService;
use Nucleus\DependencyInjection\BaseAspect;
use Nucleus\IService\Security\SecurityException;

/**
 * Security check aspect aspect
 */
class SecurityCheckAspect extends BaseAspect 
{
    /**
     * @return IAccessControlService
     */
    private function getAccessControlService()
    {
        return $this->getServiceContainer()->getServiceByName(IAccessControlService::NUCLEUS_SERVICE_NAME);
    }
    
    /**
     * @return \Symfony\Component\Yaml\Yaml
     */
    private function getYamlParser()
    {
        return $this->getServiceContainer()->getServiceByName('yamlParser');
    }
    
    /**
     * Cacheable methods
     *
     * @param MethodInvocation $invocation Invocation
     *
     * @Go\Lang\Annotation\Before("@annotation(Nucleus\IService\Security\Secure)")
     */
    public function aroundCacheable(MethodInvocation $invocation)
    {
        $annotation = $this->getSecureAnnotation($invocation);
        
        $result = $this->getYamlParser()->parse($annotation->permissions);
        
        if(!$this->getAccessControlService()->checkPermissions($result)) {
            throw new SecurityException('Connected user does have the credentials needed: ' . $annotation->permissions);
        }
    }
    
    /**
     * @param MethodInvocation $invocation
     * @return \Nucleus\IService\Security\Secure
     */
    private function getSecureAnnotation(MethodInvocation $invocation)
    {
        return $this->getAnnotation($invocation, 'Nucleus\IService\Security\Secure');
    }
}