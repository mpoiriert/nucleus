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

/**
 * Description of TwigDataCollector
 *
 * @author Martin
 * 
 * @\Nucleus\IService\DependencyInjection\Tag(name="debugBar.dataCollector")
 */
class TwigDataCollector extends BaseAspect implements Renderable, DataCollectorInterface, IAssetProvider
{
    private $renderedTemplates = array();   
    
    /**
     * Cacheable methods
     *
     * @param MethodInvocation $invocation Invocation
     *
     * @Go\Lang\Annotation\Around("execution(public Twig_Template->display(*))")
     */
    public function aroundDisplay(MethodInvocation $invocation)
    {
        $start = microtime(true);
        $result = $invocation->proceed();
        $end = microtime(true);

        if (!is_null($timeDataCollector = $this->getTimeDataCollector())) {
            $name = sprintf("twig.render(%s)", $invocation->getThis()->getTemplateName());
            $timeDataCollector->addMeasure($name, $start, $end);
        }

        $this->renderedTemplates[] = 
            array(
            'name' => $invocation->getThis()->getTemplateName(),
            'render_time' => $end - $start
        );
        
        return $result;
    }
    
    public function formatDuration($seconds)
    {
        if ($seconds < 1) {
            return round($seconds * 1000) . 'ms';
        }
        return round($seconds, 2) . 's';
    }
    
    /**
     * 
     * @return \DebugBar\DataCollector\TimeDataCollector
     */
    private function getTimeDataCollector()
    {
        return $this->getDebugBar()->getCollector('time');
    }

     /**
     * {@inheritDoc}
     */
    public function collect()
    {
        $templates = array();
        $accuRenderTime = 0;

        foreach ($this->renderedTemplates as $tpl) {
            $accuRenderTime += $tpl['render_time'];
            $templates[] = array(
                'name' => $tpl['name'],
                'render_time' => $tpl['render_time'],
                'render_time_str' => $this->formatDuration($tpl['render_time'])
            );
        }

        return array(
            'nb_templates' => count($templates),
            'templates' => $templates,
            'accumulated_render_time' => $accuRenderTime,
            'accumulated_render_time_str' => $this->formatDuration($accuRenderTime)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getWidgets()
    {
        return array(
            'twig' => array(
                'widget' => 'PhpDebugBar.Widgets.TemplatesWidget',
                'map' => 'twig',
                'default' => '[]'
            ),
            "twig:badge" => array(
                "map" => "twig.nb_templates",
                "default" => 0
            )
        );
    }
    
    /**
     * 
     * @return \Nucleus\DebugBar\NucleusDebugBar
     */
    public function getDebugBar()
    {
        return $this->getServiceContainer()->getServiceByName('debugBar');
    }

    public function getName()
    {
        return 'twig';
    }

    public function getCssFiles()
    {
        return array();
    }

    public function getJsFiles()
    {
        return array();
    }
}
