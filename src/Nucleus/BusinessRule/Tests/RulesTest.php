<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\BusinessRuleEngine\Tests;

use Nucleus\BusinessRule\BusinessRuleEngine;
use DateTime;

/**
 * Description of DateBeforeTest
 *
 * @author Martin
 * 
 * @group integration
 */
class RulesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Nucleus\BusinessRule\BusinessRuleEngine 
     */
    private $businessRuleEngine;

    public function setUp()
    {
        $this->businessRuleEngine = BusinessRuleEngine::factory(
                array(
                    'imports' => array(__DIR__ . '/../nucleus.json'),
                    'services' => array(
                        'clock' => array(
                            'configuration' => array('now' => '2000-01-01 00:00:00')
                        )
                    )
                )
        );
    }

    /**
     * @dataProvider provideDate
     */
    public function testDate($ruleComposition, $expected, DateTime $dateTime = null)
    {
        $contexParameters = array();
        if (!is_null($dateTime)) {
            $contexParameters[] = $dateTime;
        }
        $this->assertSame(
            $expected, $this->businessRuleEngine->check($ruleComposition, "default", $contexParameters)
        );
    }

    public function provideDate()
    {
        //Don't forget that the Clock have been initialize with '2000-01-01 00:00:00'
        return array(
            array(array("date\before{date:'2013-04-04'}"), true),
            array(array("date\before{date:'1998-04-04'}"), false),
            array(array("date\after{date:'2013-04-04'}"), false),
            array(array("date\after{date:'1998-04-04'}"), true),
        );
    }
}
