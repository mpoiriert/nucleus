<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */



/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Security;

/**
 * Description of Console
 *
 * @author Martin Poirier
 * 
 * @Annotation
 * 
 * @Target({"METHOD"})
 */
class Secure
{
    /**
     * The permission needed for the format see symfony 1 documentation
     * 
     * Here is some exemple
     * 
     * [admin]
     * [user,powerUser]
     * 
     * It must use the business rule engine service format
     * 
     * @link http://symfony.com/legacy/doc/reference/1_4/en/08-Security Symfony 1 credentials format
     * 
     * @var string  
     */
    public $permissions;
}
