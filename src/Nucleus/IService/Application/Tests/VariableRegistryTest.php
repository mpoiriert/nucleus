<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Application\Tests;

use PHPUnit_Framework_TestCase;

/**
 * Description of CacheServiceTest
 *
 * @author Martin
 */
abstract class VariableRegistryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Nucleus\IService\Application\IVariableRegistry
     */
    private $applicationVariableRegistry;

    /**
     * @return Nucleus\IService\Application\IVariableRegistry
     */
    abstract protected function getApplicationVariableRegistry();

    /**
     * @return Nucleus\IService\Application\IVariableRegistry
     */
    private function loadApplicationVariableRegistry()
    {
        if (is_null($this->applicationVariableRegistry)) {
            $this->applicationVariableRegistry = $this->getApplicationVariableRegistry();
            $this->assertInstanceOf('\Nucleus\IService\Application\IVariableRegistry', $this->applicationVariableRegistry);
        }

        return $this->applicationVariableRegistry;
    }
    
    public function testAllNoNamespace()
    {
        $variableRegistry = $this->loadApplicationVariableRegistry();
        
        $this->assertFalse(
            $variableRegistry->has("test"), 
            'The variable does not exists.'
        );
        
        $variableRegistry->set("test","value");
        $this->assertTrue(
            $variableRegistry->has("test"), 
            'The variable does exists after have been set.'
        );
         
        $this->assertEquals(
            "value",
            $variableRegistry->get("test"),
            'The variable has the good value.'
        );
        
        $this->assertFalse(
            $variableRegistry->has("test","otherNamespace"),
            'The same variable name in another namespace is not set.'
        );
        
        $variableRegistry->delete("test");
        
        $this->assertFalse(
            $variableRegistry->has("test"),
            'The variable test have been deleted properly.'
        );
        
        $this->assertFalse(
            $variableRegistry->has("test2"),
            'Another variable name does not exists.'
        );
        
        $this->assertEquals(
            "value2",
            $variableRegistry->get("test2","value2"),
            'The default value is return properly.'
        );
        
        $this->assertFalse($variableRegistry->has("test2"));
    }
    
    public function testAllWithNamespace()
    {
        $variableRegistry = $this->loadApplicationVariableRegistry();
        
        $this->assertFalse(
            $variableRegistry->has("test","otherNamespace"),
            'The variable does not exists.'
        );
        
        $variableRegistry->set("test","value3","otherNamespace");
        
        $this->assertTrue(
            $variableRegistry->has("test","otherNamespace"),
            'The variable exists after it have been set.'
        );
         
        $this->assertEquals(
            "value3",
            $variableRegistry->get("test",null,"otherNamespace"),
            "The variable have the good value"
        );
        
        $this->assertFalse(
            $variableRegistry->has("test"),
            'The same variable name in the default namespace does not exists.'
         );
        
        $variableRegistry->delete("test","otherNamespace");
        
        $this->assertFalse(
            $variableRegistry->has("test","otherNamespace"),
            'The variable does not exists after deletion.'
         );
    }
}