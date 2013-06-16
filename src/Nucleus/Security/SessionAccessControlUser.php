<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Security;

use Nucleus\IService\Security\IAccessControlUser;

/**
 * Description of SessionAccessControlUser
 *
 * @author Martin
 */
class SessionAccessControlUser implements IAccessControlUser
{
    /**
     * @BoundToSession
     * 
     * @var string[]
     */
    private $permissions = array();

    public function getPermissions()
    {
        return $this->permissions;
    }

    public function addPermission($permission)
    {
        if (!in_array($permission, $this->permissions)) {
            $this->permissions[] = $permission;
        }
    }

    public function addPermissions($permissions)
    {
        //We do a array_values since we want to be sure that all the
        //index are consecutive
        $this->permissions = array_values(
            array_unique(
                array_merge($this->permissions, $permissions)
            )
        );
    }
}
