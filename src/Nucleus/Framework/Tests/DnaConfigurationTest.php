<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Framework\Test;

use Nucleus\Framework\DnaConfiguration;

/**
 * Description of DnaConfigurationTest
 *
 * @author Martin
 */
class DnaConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideTestPrepend
     */
    public function testPrepend()
    {
        $arguments = func_get_args();
        $expected = array_pop($arguments);
        $configuration = new DnaConfiguration(__DIR__);
        
        foreach($arguments as $prependConfiguration) {
            $configuration->prependConfiguration($prependConfiguration);
        }
        
        $this->assertEquals($expected, $configuration->getConfiguration());
    }
    
    public function provideTestPrepend()
    {
        return array(
            array(
                'test.json',
                'test.json'
            ),
            array(
                'test.json',
                'prepend.json',
                array('imports'=>array('prepend.json','test.json'))
            ),
            array(
                array('configuration'=>array('second')),
                array('configuration'=>array('first')),
                array('configuration'=>array('first','second'))
            ),
            array(
                array('configuration'=>array('array')),
                'prepend.json',
                array('configuration'=>array('array'),'imports'=>array('prepend.json'))
            ),
            array(
                'current.json',
                array('configuration'=>array('array')),
                array('configuration'=>array('array'),'imports'=>array(array('append'=>true,'file'=>'current.json')))
            )
        );
    }
    
    /**
     * @dataProvider provideTestAppend
     */
    public function testAppend()
    {
        $arguments = func_get_args();
        $expected = array_pop($arguments);
        $configuration = new DnaConfiguration(__DIR__);
        
        foreach($arguments as $prependConfiguration) {
            $configuration->appendConfiguration($prependConfiguration);
        }
        
        $this->assertEquals($expected, $configuration->getConfiguration());
    }
    
    public function provideTestAppend()
    {
        return array(
            array(
                'test.json',
                'test.json'
            ),
            array(
                'test.json',
                'append.json',
                array('imports'=>array('test.json','append.json'))
            ),
            array(
                array('configuration'=>array('first')),
                array('configuration'=>array('second')),
                array('configuration'=>array('first','second'))
            ),
            array(
                array('configuration'=>array('array')),
                'append.json',
                array('configuration'=>array('array'),'imports'=>array(array('append'=>true,'file'=>'append.json')))
            ),
            array(
                'current.json',
                array('configuration'=>array('array')),
                array('configuration'=>array('array'),'imports'=>array('current.json'))
            )
        );
    }
}
