<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Clock;

/**
 * Class usefull for unit testing so time can be injected
 * 
 * @author Martin
 */
interface IClock
{

    /**
     * @param int $timestamp The time that you want to set base on strtotime() format
     */
    public function setNow($time);

    /**
     * Alter the 'now()' time and set it as the current time.
     * 
     * @see strtotime()
     * 
     * @param string $time Same string format as strtotime()
     */
    public function alter($time);

    /**
     * Return the time set has now or the current 'time()' if is null
     * 
     * @return mixed 
     */
    public function now($format = "U");

    /**
     * Return the timestamp diffrence between the time() and the IClock::now()
     * 
     * @return int
     */
    public function getTimestampDifference();

    /**
     * Return a string to time base on the now date. If strtotime refer to a date
     * it will use the date. If all the element are relative it will use the
     * now reference.
     * 
     * @param string $time
     */
    public function strtotime($time);
}