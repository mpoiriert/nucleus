<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Cache;

/**
 * This annotation mean that the system must clear this specific namespace
 * after the execution of the method
 *
 * @author Martin
 * 
 * @Annotation
 * 
 * @Target({"METHOD"})
 */
class ClearNamespace
{
    /**
     * @var string
     */
    public $namespace = ICacheService::NAMESPACE_DEFAULT;
}
