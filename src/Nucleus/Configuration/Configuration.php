<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Configuration;

use \Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Description of Configuration
 *
 * @author Martin
 */
class Configuration
{
    const NUCLEUS_SERVICE_NAME = "configuration";

    private $propertyAccessor;
    private $configuration = array();

    public function __construct(array $configuration = array())
    {
        $this->merge($configuration);
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public function merge(array $configuration = array())
    {
        $this->configuration = array_deep_merge($this->configuration, $configuration);
    }

    public function get($path)
    {
        return $this->propertyAccessor->getValue($this->configuration, $path);
    }
}
