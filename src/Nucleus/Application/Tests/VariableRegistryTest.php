<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Application\Tests;

use Nucleus\IService\Application\Tests\VariableRegistryTest as BaseVariableRegistryTest;
use Nucleus\Application\NotPersistentVariableRegistry;

/**
 * Description of VariableRegsitryTest
 *
 * @author Martin
 */
class VariableRegistryTest extends BaseVariableRegistryTest
{

    protected function getApplicationVariableRegistry()
    {
        return new NotPersistentVariableRegistry();
    }
}