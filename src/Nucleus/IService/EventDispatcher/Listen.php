<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\EventDispatcher;

/**
 * Description of Inject
 *
 * @Annotation
 */
class Listen
{
    /**
     * @var string
     */
    public $eventName;

    /**
     * @var int
     */
    public $priority = 0;
}