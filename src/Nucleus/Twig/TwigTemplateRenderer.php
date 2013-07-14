<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Twig;

use Twig_Environment;
use Nucleus\View\BaseExtensionRenderer;
use Nucleus\Framework\Nucleus;

/**
 * Description of TwigRenderer
 *
 * @author Martin
 */
class TwigTemplateRenderer extends BaseExtensionRenderer
{
    /**
     * @var Twig_Environment 
     */
    private $twig = null;

    public function __construct()
    {
        $this->setExtensions(array('twig'));
    }

    /**
     * @Inject
     */
    public function setTwig(Twig_Environment $twigEnvironment)
    {
        $this->twig = $twigEnvironment;
    }

    public function render($template, array $parameters = array())
    {
        return $this->twig->render($template, $parameters);
    }
    
    /**
     * @param mixed $configuration
     * @return TwigRenderer
     */
    public static function factory($configuration = null)
    {
        if (is_null($configuration)) {
            $configuration = __DIR__ . '/nucleus.json';
        }

        return Nucleus::serviceFactory($configuration, 'templateRenderer.twig');
    }
}
