<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Security;

use Nucleus\IService\Security\IAccessControlService;
use Nucleus\IService\Security\IAccessControlUser;
use Nucleus\BusinessRule\BusinessRuleEngine;
use Nucleus\Framework\Nucleus;

/**
 * Description of AccessControlManager
 *
 * @author Martin
 */
class AccessControlManager implements IAccessControlService
{
    const BUSINESS_RULE_CONTEXT = "security";

    /**
     * @var \Nucleus\BusinessRule\BusinessRuleEngine
     */
    private $businessRuleEngine;

    /**
     * @var \Nucleus\IService\Security\IAccessControlUser 
     */
    private $accessControlUser;

    /**
     * @param \Nucleus\BusinessRule\BusinessRuleEngine $businessRuleEngine
     * 
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function initialize(BusinessRuleEngine $businessRuleEngine)
    {
        $this->businessRuleEngine = $businessRuleEngine;
    }

    /**
     * @param \Nucleus\IService\Security\IAccessControlUser $accessControlUser
     * 
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function setAccessControlUser(IAccessControlUser $accessControlUser)
    {
        $this->accessControlUser = $accessControlUser;
    }

    public function checkPermissions(array $permissionRules, IAccessControlUser $accessControlerUser = null)
    {
        if (is_null($accessControlerUser)) {
            $accessControlerUser = $this->accessControlUser;
        }

        return $this->businessRuleEngine->check(
            $permissionRules, self::BUSINESS_RULE_CONTEXT, array($accessControlerUser)
        );
    }

    /**
     * @param mixed $configuration
     * @return IAccessControlService
     */
    public static function factory($configuration = null)
    {
        if (is_null($configuration)) {
            $configuration = __DIR__ . '/nucleus.json';
        }

        return Nucleus::serviceFactory($configuration, self::NUCLEUS_SERVICE_NAME);
    }
}
