<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Twig;

use Twig_Template;

/**
 * Description of BaseTwigTemplate
 *
 * @author Martin
 * 
 * 
 */
abstract class TwigTemplate extends Twig_Template
{

    public function render(array $context)
    {
        foreach ($this->env->getExtensions() as $extension) {
            if (!method_exists($extension, 'preRenderTemplate')) {
                continue;
            }
            $extension->preRenderTemplate($this);
        }
        $result = parent::render($context);
        foreach ($this->env->getExtensions() as $extension) {
            if (!method_exists($extension, 'postRenderTemplate')) {
                continue;
            }
            $result = $extension->postRenderTemplate($this, $result);
        }
        return $result;
    }
}
