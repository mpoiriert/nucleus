<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Security;

use Nucleus\IService\Security\IAccessControlUser;

/**
 * Description of CheckPermissionRule
 *
 * @author Martin
 */
class CheckPermissionRule
{

    public function __invoke($permission, IAccessControlUser $userControl)
    {
        return in_array($permission, $userControl->getPermissions());
    }
}
