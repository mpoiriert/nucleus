<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\DependencyInjection;

/**
 * Description of Inject
 *
 * @Annotation
 */
class Inject
{
    /**
     * @var string[]
     */
    private $mapping;

    public function __construct($values)
    {
        $this->mapping = $values;
    }

    public function getMapping()
    {
        return $this->mapping;
    }
}