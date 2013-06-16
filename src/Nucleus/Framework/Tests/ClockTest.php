<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Framework\Tests;

use Nucleus\Framework\Clock;

/**
 * Description of ClockTest
 *
 * @author Martin
 */
class ClockTest extends \PHPUnit_Framework_TestCase
{

    public function testNow()
    {
        $clock = new Clock();
        $this->assertEquals(time(), $clock->now());
    }

    public function testSetNow()
    {
        $clock = new Clock();
        $clock->setNow('2013-05-14');
        $this->assertEquals($clock->now(), strtotime('2013-05-14'));
    }

    public function testAlter()
    {
        $clock = new Clock();
        $clock->setNow('2013-05-14');
        $clock->alter('+ 10 seconds');
        $this->assertEquals($clock->now(), strtotime('2013-05-14') + 10);
    }

    public function testStrtotime()
    {
        $clock = new Clock();
        $clock->setNow('2000-01-01');
        $this->assertEquals('2000-01-16', date('Y-m-d', $clock->strtotime('January +15 days')));
    }
}
