<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Twig\Tests;

use Twig_Test_IntegrationTestCase;
use Nucleus\DebugBar\TwigDebugBarExtension;
use DebugBar\DebugBar;

/**
 * Description of TwigRendererTest
 *
 * @author Martin
 */
class TwigDebugBarExtensionTest extends Twig_Test_IntegrationTestCase
{
    protected function getExtensions()
    {
        $extension = new TwigDebugBarExtension();
        $debugBar = new DebugBar();
        $extension->initialize(new JavascriptRenderer($debugBar), $debugBar);
        return array($extension);
    }

    protected function getFixturesDir()
    {
        return __DIR__ . '/fixtures/debugBar';
    }
}

class JavascriptRenderer extends \DebugBar\JavascriptRenderer
{
    public function render($initialize = true)
    {
        return 'debugToolbar';
    }
    
    public function renderHead()
    {
        return 'debugIncludes';
    }
}