<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\DependencyInjection;

/**
 * @Annotation
 * 
 * @Target({"CLASS"})
 */
class Tag
{
    /**
     * @var string
     */
    public $name;
}