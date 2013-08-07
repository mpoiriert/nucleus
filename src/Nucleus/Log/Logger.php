<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Log;

use Monolog\Logger as BaseLogger;

/**
 * Description of Logger
 *
 * @author Martin
 */
class Logger extends BaseLogger
{
    /**
     * @param string $name
     * @param array $handlers
     * @param array $processors
     * 
     * @\Nucleus\IService\DependencyInjection\Inject(name="$[logger][name]",handlers="@logger.handler",processors="@logger.processor")
     */
    public function __construct($name, array $handlers = array(), array $processors = array())
    {
        parent::__construct($name, $handlers, $processors);
    }
}
