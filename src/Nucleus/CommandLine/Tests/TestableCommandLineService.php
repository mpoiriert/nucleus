<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Nucleus\CommandLine\Tests;
/**
 * Description of TestableCommandLineService
 *
 * @author AxelBarbier
 */
class TestableCommandLineService {
    public $fakeCalls = 0;
    
    
    
    /**
     * Comment from the function 
     * @\Nucleus\IService\CommandLine\Consolable(name="test")
     */
    public function commandLineWithName(){
        echo __FUNCTION__;
    }

}