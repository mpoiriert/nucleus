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
     * @Inject
     */
    public function setTwig(Twig_Environment $twigEnvironment)
    {
        $this->twig = $twigEnvironment;
    }

    public function getTwig()
    {
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
        foreach ($extensions as $extension) {
            $this->twig->addExtension($extension);
        }
    }

    public function render($file, array $parameters = array())
    {
        if (file_exists($file)) {
            $this->twig->getArrayLoader()->setTemplate($file, file_get_contents($file));
        }
        return $this->twig->render($file, $parameters);
    }
}
