<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Migration;

use Nucleus\IService\Migration\IMigrationTask;
use ReflectionClass;

/**
 *
 * @author mcayer
 */
abstract class BaseMigrationTask implements IMigrationTask
{
    protected $parameters;

    public function prepare(array $parameters)
    {
        $this->parameters = $parameters;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getUniqueId()
    {
        return md5(get_class($this) . md5(serialize($this->parameters)));
    }
}
