<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\View;

use Twig_Loader_Filesystem;
use ReflectionMethod;

/**
 * Description of FileSystemLoader
 *
 * @author Martin
 */
class FileSystemLoader
{
    private $twigFileSystemLoader;
    private $findTemplateReflectionMethod;

    public function __construct(array $paths = array())
    {
        $this->twigFileSystemLoader = new Twig_Loader_Filesystem(array());
        $this->findTemplateReflectionMethod = new ReflectionMethod(get_class($this->twigFileSystemLoader), 'findTemplate');
        $this->findTemplateReflectionMethod->setAccessible(true);
        $this->setPaths($paths);
    }

    /**
     * @param array $configuration
     * 
     * @Inject(configuration="$")
     */
    public function initialize(array $configuration)
    {
        if (isset($configuration['paths'])) {
            $this->setPaths($configuration['paths']);
        }
    }

    public function setPaths(array $paths = array())
    {
        $this->twigFileSystemLoader->setPaths($paths);
    }

    public function addPath($path)
    {
        $this->twigFileSystemLoader->addPath($path);
    }

    public function getPaths()
    {
        return $this->twigFileSystemLoader->getPaths();
    }

    public function exists($fileName)
    {
        return file_exists($fileName) || $this->twigFileSystemLoader->exists($fileName);
    }

    public function getFullPath($fileName)
    {
        return $this->findTemplateReflectionMethod->invoke(
                $this->twigFileSystemLoader, $fileName
        );
    }
}
