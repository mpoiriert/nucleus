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
interface IAccessControlUser
{

    /**
     * @return array
     */
    public function getPermissions();
    
    public function addPermission($permission);
    
    public function addPermissions($permissions);
    
    public function clearPermissions();
}
