<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Migration;

/**
 *
 * @author Martin
 * @author mcayer
 */
interface IMigrationTask
{

    /**
     * Prepare the task to be run. If a task must be run multiple time with
     * different parameters the same instance can be use but prepare will always
     * be call before the run. 
     * 
     * This rules also apply to the getUniqueId method so the paremeters
     * can influence the unique id value.
     * 
     * @param array $parameters
     */
    public function prepare(array $parameters);

    /**
     * Run the task
     */
    public function run();

    /**
     * Return a unique id of the task so we can know if it have bee run
     * 
     * @return string
     */
    public function getUniqueId();
}