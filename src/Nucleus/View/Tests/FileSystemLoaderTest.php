<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\View\Tests;

use Nucleus\View\FileSystemLoader;

/**
 * Description of FileSystemLoader
 *
 * @author Martin
 */
class FileSystemLoaderTest extends \PHPUnit_Framework_TestCase
{
    private $loader = null;

    public function setUp()
    {
        $this->loader = new FileSystemLoader();
    }

    public function testExists()
    {
        $this->loader->setPaths(array(__DIR__));
        $this->assertTrue($this->loader->exists('FileSystemLoaderTest.php'));
        $this->assertTrue($this->loader->exists('fixtures/file.php'));
        $this->assertFalse($this->loader->exists('noExisting.php'));
    }

    public function testAddPath()
    {
        $this->assertFalse($this->loader->exists('file.php'));
        $this->loader->addPath(__DIR__ . '/fixtures');
        $this->assertTrue($this->loader->exists('file.php'));
    }

    public function setGetPaths()
    {
        $paths = array(__DIR__, __DIR__ . '/fixtures');
        $this->loader->setPaths($paths);
        $this->assertEquals($paths, $this->loader->getPaths());
    }

    public function testLoadingOrder()
    {
        $fileName = 'file.php';
        $this->assertFalse($this->loader->exists($fileName));
        $dir = str_replace('\\', '/', __DIR__);
        $paths = array($dir . '/fixtures');
        $this->loader->setPaths($paths);
        $this->assertEquals($dir . '/fixtures/file.php', $this->loader->getFullPath($fileName));

        array_unshift($paths, $dir . '/fixtures/folder1');
        $this->loader->setPaths($paths);
        $this->assertEquals($dir . '/fixtures/folder1/file.php', $this->loader->getFullPath($fileName));

        array_unshift($paths, $dir . '/fixtures/folder2');
        $this->loader->setPaths($paths);
        $this->assertEquals($dir . '/fixtures/folder2/file.php', $this->loader->getFullPath($fileName));
    }
}
