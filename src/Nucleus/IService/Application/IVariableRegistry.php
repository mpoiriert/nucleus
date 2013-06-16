<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Application;

/**
 * This is you by the applciation to access the application variable. They
 * normally should be persisted among request call. 
 * 
 * The value will most likely be serialize so be sure that everything you want
 * to store is serializable/unserializable
 *
 * @author Martin
 */
interface IVariableRegistry
{
    const NUCLEUS_SERVICE_NAME = "applicationVariableRegistry";
  
    /**
     * Get the value from the registry or the default value if it doesn't
     * exist.
     * 
     * @param string $name
     * @param mixed $default
     * @param string $namespace
     * 
     * @return mixed
     */
    public function get($name, $default = null, $namespace = 'default');
    
    /**
     * Set the value in the registry
     * 
     * @param string $name
     * @param mixed $value
     * @param string $namespace
     */
    public function set($name, $value, $namespace = 'default');
    
    /**
     * Verify if a variable is present in the registry
     * 
     * @param type $name
     * @param type $namespace
     * 
     * @return boolean
     */
    public function has($name, $namespace = 'default');
    
    /**
     * Delete the variable from the registry
     * 
     * @param string $name
     * @param string $namespace
     */
    public function delete($name, $namespace = 'default');
}
