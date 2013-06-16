<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Twig;

use Twig_Loader_Array;
use Twig_Loader_Chain;
use Twig_Environment;
use Twig_Loader_Filesystem;
use Nucleus\View\BaseExtensionRenderer;

/**
 * Description of TwigRenderer
 *
 * @author Martin
 */
class TwigRenderer extends BaseExtensionRenderer
{
    /**
     * @var \Twig_Environment 
     */
    private $twig = null;

    /**
     *
     * @var \Twig_Extension[] 
     */
    private $twigExtensions = array();

    /**
     *
     * @var \Twig_Loader_Array
     */
    private $arrayLoader = null;

    /**
     *
     * @var array
     */
    private $configuration = array();

    public function __construct()
    {
        $this->setExtensions(array('twig'));
    }

    /**
     * 
     * @param string $cacheDirectory
     * @param string $viewDirectory
     * 
     * @Inject(
     *   configuration="$",
     *   cacheDirectory="$[configuration][generatedDirectory]",
     *   debug="$[configuration][debug]"
     * )
     */
    public function initialize($configuration, $cacheDirectory, $debug)
    {
        $this->configuration = $configuration;
        $this->configuration["twigEnvironment"]["debug"] = $debug;
        $this->configuration["twigEnvironment"]["cache"] = $cacheDirectory . '/twig';
    }

    /**
     * @return \Twig_Environment
     */
    private function getTwig()
    {
        if (is_null($this->twig)) {
            $loader = new Twig_Loader_Chain();
            $this->arrayLoader = new Twig_Loader_Array(array());
            $loader->addLoader($this->arrayLoader);
            $loader->addLoader(
                new Twig_Loader_Filesystem($this->getFileSystemLoader()->getPaths())
            );

            $this->twig = new Twig_Environment(
                $loader, $this->configuration["twigEnvironment"]
            );

            foreach ($this->twigExtensions as $extension) {
                $this->twig->addExtension($extension);
            }
        }

        return $this->twig;
    }

    /**
     * 
     * @param \Twig_Extension[] $extensions
     * 
     * @Inject(extensions="@twigRenderer.twigExtension")
     */
    public function setTwigExtensions(array $extensions)
    {
        $this->twigExtensions = $extensions;
    }

    public function render($file, array $parameters = array())
    {
        $twig = $this->getTwig();
        if (file_exists($file)) {
            $this->arrayLoader->setTemplate($file, file_get_contents($file));
        }
        return $twig->render($file, $parameters);
    }
}
