<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

use Nucleus\Framework\SingletonApplicationKernel;

/**
 * Description of ApplicationKernel
 *
 * @author Martin
 */
class ApplicationKernel extends SingletonApplicationKernel
{
    protected function getDnaConfiguration()
    {
        return parent::getDnaConfiguration()
            ->setCachePath(realpath(dirname(__DIR__) . '/cache'));
    }
}
