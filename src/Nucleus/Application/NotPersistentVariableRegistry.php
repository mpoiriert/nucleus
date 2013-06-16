<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Application;

use Nucleus\IService\Application\IVariableRegistry;

/**
 * Description of NotPersistentVariableRegistry
 *
 * @author Martin
 */
class NotPersistentVariableRegistry implements IVariableRegistry
{
    private $data = array();

    public function delete($name, $namespace = 'default')
    {
        unset($this->data[$namespace][$name]);
    }

    public function get($name, $default = null, $namespace = 'default')
    {
        if (!$this->has($name, $namespace)) {
            return $default;
        }

        return $this->data[$namespace][$name];
    }

    public function has($name, $namespace = 'default')
    {
        return isset($this->data[$namespace][$name]);
    }

    public function set($name, $value, $namespace = 'default')
    {
        $this->data[$namespace][$name] = $value;
    }
}
