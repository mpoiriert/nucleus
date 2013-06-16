<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\BusinessRule\Tests;

use Nucleus\Framework\Clock;
use Nucleus\BusinessRule\Rule\DateAfter;
use DateTime;

/**
 * Description of RuleDateAterTest
 *
 * @author Martin
 */
class RuleDateAfterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Nucleus\Framework\Clock 
     */
    private $clock;

    public function setUp()
    {
        $this->clock = new Clock();
        $this->clock->setNow('2000-01-01 00:00:00');
    }

    /**
     * @dataProvider provide
     */
    public function test($date, $toCompare, $exepected)
    {
        $rule = new DateAfter();
        $rule->initialize($this->clock);
        if ($toCompare) {
            $toCompare = DateTime::createFromFormat("U", ($this->clock->strtotime($toCompare)));
        }

        $this->assertSame($exepected, $rule($date, $toCompare));
    }

    public function provide()
    {
        //Don't forget that the Clock have been initialize with '2000-01-01 00:00:00'
        return array(
            array("2013-04-04", null, false),
            array("1998-04-04", null, true),
            array("+ 10 seconds", null, false),
            array("- 10 seconds", null, true),
            array("+ 10 seconds", '+ 5 seconds', false),
            array("- 10 seconds", '- 5 seconds', true),
            array("- 10 seconds", '2011-10-01', true),
            array("2012-01-01", '2011-10-01', false),
            array("2010-01-01", '2011-10-01', true),
        );
    }
}
