<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Security;

/**
 *
 * @author Martin
 */
interface IAccessControlService
{
    const NUCLEUS_SERVICE_NAME = "accessControl";

    /**
     * @param array $permissionRules Base on the BusinessRule engine
     * @param IAccessControlUser $accessControlUser
     */
    public function checkPermissions(array $permissionRules, IAccessControlUser $accessControlUser = null);
}
