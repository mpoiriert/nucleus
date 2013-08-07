<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\DebugBar;

use DebugBar\JavascriptRenderer;
use DebugBar\DebugBar;
use Twig_Extension;
use Twig_Template;

/**
 * Description of TwigDebugBarExtension
 *
 * @author Martin
 * 
 * @\Nucleus\IService\DependencyInjection\Tag(name="twigRenderer.twigExtension")
 */
class TwigDebugBarExtension extends Twig_Extension
{
    /**
     * @var JavascriptRenderer
     */
    private $debugBarRenderer;
    
    /**
     * @var DebugBar 
     */
    private $debugBar;
    
    private $templateRenderingDepth = 0;

    public function __construct()
    {
    }

    /**
     * 
     * @param Manager $assetManager
     * 
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function initialize(JavascriptRenderer $debugBarRenderer, DebugBar $debugBar)
    {
        $this->debugBarRenderer = $debugBarRenderer;
        $this->debugBar = $debugBar;
    }

    public function preRenderTemplate(Twig_Template $template)
    {
        $this->templateRenderingDepth++;
    }

    public function postRenderTemplate(Twig_Template $template, $result)
    {
        $this->templateRenderingDepth--;
        if ($this->templateRenderingDepth == 0) {
            $result = $this->renderBar($result);
        }
        return $result;
    }
    
    protected function renderBar($result)
    {
        if(strpos($result, '</head>') === false || strpos($result, '</body>') === false){
            return $result;
        }

        $includes = $this->debugBarRenderer->renderHead();
        
        $this->debugBar->collect();
        $toolbar = $this->debugBarRenderer->render();
        
        $result = $this->insertAt($result,strpos($result, '</head>'),$includes);
        return $this->insertAt($result,strpos($result, '</body>'),$toolbar);
    }
    
    protected function insertAt($string,$position,$stringToInsert)
    {
      return substr($string, 0, $position) . $stringToInsert . substr($string, $position);
    }

    public function getName()
    {
        return 'debug_bar';
    }
}
