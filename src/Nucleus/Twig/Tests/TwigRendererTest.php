<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Twig\Tests;

use Nucleus\Twig\TwigRenderer;
use Nucleus\View\FileSystemLoader;
use PHPUnit_Framework_TestCase;

/**
 * Description of TwigRendererTest
 *
 * @author Martin
 */
class TwigRendererTest extends PHPUnit_Framework_TestCase
{
    private $renderer = null;

    public function setUp()
    {
        $this->renderer = new TwigRenderer();
        $this->renderer->initialize(array(), sys_get_temp_dir() . '/' . uniqid(), false);
        $this->renderer->setFileLoader(new FileSystemLoader(array(__DIR__)));
    }

    public function testCanRender()
    {
        $this->assertTrue($this->renderer->canRender('/fixtures/toRender.twig'));
        $this->assertTrue($this->renderer->canRender('/fixtures/toRender'));
        $this->assertFalse($this->renderer->canRender('notexistingFile.twig'));
        $this->assertFalse($this->renderer->canRender('/fixtures/toRender.badExtension'));
    }

    public function testRender()
    {
        $this->assertEquals(
            "", $this->renderer->render('fixtures/toRender.twig')
        );
    }
}
