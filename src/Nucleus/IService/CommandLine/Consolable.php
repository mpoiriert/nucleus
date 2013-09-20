<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\CommandLine;

/**
 * Description of Console
 *
 * @author Axel Barbier
 * 
 * @Annotation
 * 
 * @Target({"METHOD"})
 */
class Consolable
{
    /**
     * Will be the name for command 
     * @var string  
     */
    public $name;
}
