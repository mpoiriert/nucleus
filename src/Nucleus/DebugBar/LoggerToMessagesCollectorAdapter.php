<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\DebugBar;

use DebugBar\DebugBar;
use Monolog\Handler\AbstractProcessingHandler;

/**
 * Description of LoggerToMessagesCollectorAdapter
 *
 * @author Martin
 * 
 * @\Nucleus\IService\DependencyInjection\Tag(name="logger.handler")
 */
class LoggerToMessagesCollectorAdapter extends AbstractProcessingHandler
{
    /**
     * @var DebugBar 
     */
    private $debugBar;
    
    /**
     * @param \DebugBar\DebugBar $debugBar
     * 
     * @Inject
     */
    public function setDebugBar(DebugBar $debugBar)
    {
        $this->debugBar = $debugBar;
    }
    
    protected function write(array $record)
    {
        if(!$this->debugBar->hasCollector('messages')) {
            return;
        }
     
        $this->debugBar["messages"]->addMessage($record['formatted'],$record['level_name']);
    }
}
