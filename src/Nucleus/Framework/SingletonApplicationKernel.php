<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Framework;

use UnexpectedValueException;

/**
 * Singleton Application Wraper arround nucleus usefull for application that
 * will not have more than one nucleus instance running. Allow also to access
 * the nucleus from a singleton point of view. This is not always the best way
 * to work but it might be the only one some time.
 *
 * @author Martin
 */
abstract class SingletonApplicationKernel
{
    /**
     * @var SingletonApplicationKernel 
     */
    private static $instance;

    /**
     * @var Nucleus 
     */
    private $nucleus;

    /**
     * Return the configuration need to load the nucleus instance
     * 
     * @return DnaConfiguration
     */
    protected function getDnaConfiguration()
    {
        return new DnaConfiguration();
    }

    /**
     * 
     * @return SingletonApplicationKernel
     * @throws \RuntimeException
     */
    static function createInstance()
    {
        if (!is_null(self::$instance)) {
            throw new \RuntimeException('Nucleus application kernel instance already created');
        }

        $application = self::$instance = new static();
        $dnaConfiguration = self::$instance->getDnaConfiguration();

        if (!($dnaConfiguration instanceof DnaConfiguration)) {
            throw new UnexpectedValueException("The return value for [getDnaConfiguration] should be a instance of [Nucleus\Framework\DnaConfiguration]");
        }

        $application->nucleus = Nucleus::factory($dnaConfiguration);
     
        return $application;
    }

    /**
     * Get the nucleus application base on the kernel
     * 
     * @return Nucleus
     */
    public function getNucleus()
    {
        return $this->nucleus;
    }

    /**
     * @return SingletonApplicationKernel
     */
    static public function getInstance()
    {
        if (is_null(self::$instance)) {
            throw new \RuntimeException('Nucleus application kernel instance not created');
        }

        return self::$instance;
    }
}
