<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Twig\Tests;

use Nucleus\Twig\TwigTemplateRenderer;
use PHPUnit_Framework_TestCase;

/**
 * Description of TwigRendererTest
 *
 * @author Martin
 */
class TwigTemplateRendererTest extends PHPUnit_Framework_TestCase
{
    private $renderer = null;

    public function setUp()
    {
        $this->renderer = TwigTemplateRenderer::factory(
            array(
                'imports' => array(__DIR__ . '/..'),
                'services' => array(
                    "twigEnvironment" => array(
                        "configuration" => array(
                            "cache" => false,
                            "debug" => false
                        )
                    )
                )
            )
        );
        $this->renderer->getFileSystemLoader()->addPath(__DIR__);
    }

    public function testCanRender()
    {
        $this->assertTrue($this->renderer->canRender(__DIR__ . '/fixtures/toRender.twig'));
        $this->assertTrue($this->renderer->canRender('/fixtures/toRender'));
        $this->assertFalse($this->renderer->canRender('notexistingFile.twig'));
        $this->assertFalse($this->renderer->canRender('/fixtures/toRender.badExtension'));
    }

    public function testRender()
    {
        $this->assertEquals(
            "", $this->renderer->render(__DIR__ . '/fixtures/toRender.twig')
        );
    }
}
