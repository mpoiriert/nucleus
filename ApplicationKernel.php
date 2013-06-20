<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

include __DIR__ . '/src/Nucleus/Framework/SingletonApplicationKernel.php';

/**
 * Description of ApplicationKernel
 *
 * @author Martin
 */
class ApplicationKernel extends \Nucleus\Framework\SingletonApplicationKernel
{
    protected function getDnaConfiguration()
    {
        return parent::getDnaConfiguration()->setCachePath(__DIR__ . '/cache');
    }
}
