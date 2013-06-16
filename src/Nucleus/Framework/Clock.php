<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Framework;

use Nucleus\IService\Clock\IClock;

/**
 * Description of Clock
 *
 * @author Martin
 */
class Clock implements IClock
{
    private $now;

    /**
     * @param array $configuration
     * 
     * @Inject(configuration="$")
     */
    public function setConfiguration(array $configuration)
    {
        if (isset($configuration['now'])) {
            $this->setNow($configuration['now']);
        }
    }

    public function setNow($time)
    {
        $result = null;
        if (!is_null($time)) {
            $result = strtotime($time);
            if ($result === false) {
                throw new \RuntimeException('The time [' . $time . '] cannot be converted to int');
            }
        }
        $this->now = $result;
    }

    public function alter($time)
    {
        $this->now = strtotime($time, $this->now());
    }

    public function now($format = "U")
    {
        return date($format, is_null($this->now) ? time() : $this->now);
    }

    public function getTimestampDifference()
    {
        return time() - $this->now();
    }

    public function strtotime($time)
    {
        if (is_null($this->now) || strtotime($time, $this->now) == strtotime($time)) {
            $result = strtotime($time);
        } else {
            $result = strtotime($time, $this->now);
        }

        if ($result === false) {
            throw new \RuntimeException('The time [' . $time . '] cannot be converted to int');
        }

        return $result;
    }
}
