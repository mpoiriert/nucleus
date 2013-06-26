<?php

namespace Nucleus\Twig;

use Twig_Environment;
use Twig_Loader_Chain;
use Twig_Loader_Array;
use Twig_Loader_Filesystem;
use Nucleus\View\FileSystemLoader;

class TwigEnvironment extends Twig_Environment
{
    /**
     * @Inject(options="$")
     */
    public function __construct($options = null)
    {
        if ($options === null) {
            $options = array();
        }
        parent::__construct(new Twig_Loader_Chain(), $options);
        $this->setBaseTemplateClass('Nucleus\Twig\TwigTemplate');
        $this->arrayLoader = new Twig_Loader_Array(array());
        $this->loader->addLoader($this->arrayLoader);
    }

    /**
     * @param \Nucleus\View\FileSystemLoader $loader
     * @Inject
     */
    public function importNucleusFileSystemLoader(FileSystemLoader $viewFileLoader)
    {
        $this->loader->addLoader(
            new Twig_Loader_Filesystem($viewFileLoader->getPaths())
        );
    }

    public function getArrayLoader()
    {
        return $this->arrayLoader;
    }
}
