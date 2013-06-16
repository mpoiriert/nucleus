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

    public function getUniqueId()
    {
        $reflectinClass = new ReflectionClass($this);
        $filename = $reflectinClass->getFileName();
        return md5(md5_file($filename) . md5(serialize($this->parameters)));
    }
}
