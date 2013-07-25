<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Security\Tests;

/**
 * Description of SecuredClass
 *
 * @author Martin
 */
class SecuredClass
{
    /**
     * @\Nucleus\IService\Security\Secure(permissions="[notExisting]")
     */
    public function impossibleCredentials()
    {
        
    }
    
    /**
     * @\Nucleus\IService\Security\Secure(permissions="[existing]")
     */
    public function possibleCredentials()
    {
        return true;
    }
}
