<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Migration;

/**
 * @author mcayer
 */
interface IMigrator
{
    /**
     * The service name use as a reference
     */
    const NUCLEUS_SERVICE_NAME = 'migrator';

    public function setConfiguration(array $configuration);

    public function runAll();

    public function markAllAsRun();
}