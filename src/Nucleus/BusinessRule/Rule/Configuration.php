<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\BusinessRule\Rule;

/**
 * Description of Configuration
 *
 * @author Martin
 */
class Configuration
{
    /**
     * @var \Nucleus\Configuration\Configuration
     */
    private $configuration;

    public function __invoke($path)
    {
        return (bool) $this->configuration->get($path);
    }
}
