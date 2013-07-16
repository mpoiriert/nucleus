<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\DebugBar\DataCollector;

use Nucleus\DependencyInjection\BaseAspect;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\DataCollectorInterface;
use Nucleus\DebugBar\IAssetProvider;
use Go\Aop\Intercept\MethodInvocation;
use Nucleus\IService\Cache\EntryNotFoundException;

/**
 * Description of CacheDataCollector
 *
 * @author Martin
 * 
 * @\Nucleus\IService\DependencyInjection\Tag(name="debugBar.dataCollector")
 */
class CacheDataCollector extends BaseAspect implements Renderable, DataCollectorInterface, IAssetProvider
{
    private $data = array('calls' => array());

    /**
     * Cacheable methods
     *
     * @param MethodInvocation $invocation Invocation
     *
     * @Go\Lang\Annotation\Around("execution(public Nucleus\Cache\BaseCacheService->get(*))")
     */
    public function arroundGet(MethodInvocation $invocation)
    {
        $arguments = $invocation->getArguments();
        $call = array('method' => 'get', 'found' => true, 'name' => $arguments[0], 'namespace' => $arguments[1]);

        $message = 'Cache get [' . $arguments[1] . '] / [' . $arguments[0] . ']';

        try {
            $result = $invocation->proceed();
            $this->data['calls'][] = $call;
            $this->getDebugBar()->getCollector('messages')
              ->addMessage($message . ' found');
            return $result;
        } catch (EntryNotFoundException $e) {
            $call['found'] = false;
            $this->data['calls'][] = $call;
            $this->getDebugBar()->getCollector('messages')
              ->addMessage($message . ' not found');
            throw $e;
        }
    }

    /**
     * Cacheable methods
     *
     * @param MethodInvocation $invocation Invocation
     *
     * @Go\Lang\Annotation\Around("execution(public Nucleus\Cache\BaseCacheService->set(*))")
     */
    public function arroundSet(MethodInvocation $invocation)
    {
        $arguments = $invocation->getArguments();
        $call = array('method' => 'set', 'name' => $arguments[0], 'namespace' => $arguments[3]);

        $this->getDebugBar()->getCollector('messages')
            ->addMessage('Cache set [' . $arguments[3] . '] / [' . $arguments[0] . ']');

        $result = $invocation->proceed();
        $this->data['calls'][] = $call;

        return $result;
    }
    
    /**
     * 
     * @return Nucleus\DebugBar\NucleusDebugBar
     */
    public function getDebugBar()
    {
        return $this->getServiceContainer()->getServiceByName('debugBar');
    }

    public function getName()
    {
        return 'cache';
    }

    public function getWidgets()
    {
        return array(
            "cache" => array(
                "widget" => "PhpDebugBar.Widgets.CacheWidget",
                "map" => "cache",
                "default" => "[]"
            ),
            "cache:badge" => array(
                "map" => "cache.nbCalls",
                "default" => 0
            )
        );
    }

    public function collect()
    {
        $this->data['nbCalls'] = count($this->data['calls']);
        return $this->data;
    }

    public function getCssFiles()
    {
        return array('nucleus/cache/cacheStyle.css');
    }

    public function getJsFiles()
    {
        return array('nucleus/cache/cacheWidget.js');
    }
}
