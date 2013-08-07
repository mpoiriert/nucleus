<?php

namespace Nucleus\Twig;

use Twig_Environment;
use Twig_Loader_Chain;
use Twig_Loader_Array;
use Nucleus\View\FileSystemLoader;

class TwigEnvironment extends Twig_Environment
{
    /**
     * @\Nucleus\IService\DependencyInjection\Inject(options="$")
     */
    public function __construct(Twig_LoaderInterface $twigLoader = null, $options = array())
    {
        if(!($twigLoader instanceof Twig_Loader_Chain)) {
            $twigLoaderChain = new Twig_Loader_Chain();
            if(!is_null($twigLoader)) {
                $twigLoaderChain->addLoader($twigLoader);
            }
        } else {
            $twigLoaderChain = $twigLoader;
        }

        parent::__construct($twigLoaderChain, $options);
        
        $this->arrayLoader = new Twig_Loader_Array(array());
        $this->loader->addLoader($this->arrayLoader);
        
        $this->setBaseTemplateClass('Nucleus\Twig\TwigTemplate');
    }

    /**
     * 
     * @param \Twig_Extension[] $extensions
     * 
     * @\Nucleus\IService\DependencyInjection\Inject(extensions="@twigRenderer.twigExtension")
     */
    public function setTwigExtensions(array $extensions)
    {
        foreach ($extensions as $extension) {
            $this->addExtension($extension);
        }
    }

    /**
     * @param \Nucleus\View\FileSystemLoader $loader
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function importNucleusFileSystemLoader(FileSystemLoader $templateFileLoader)
    {
        $this->loader->addLoader($templateFileLoader);
    }

    public function getArrayLoader()
    {
        return $this->arrayLoader;
    }
}
