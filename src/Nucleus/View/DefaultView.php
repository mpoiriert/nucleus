<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\View;

use Nucleus\IService\View\IView;

/**
 * Description of DefaultView
 *
 * @author Martin
 */
class DefaultView implements IView
{
    private $template;
    
    private $variables;
    
    public function getTemplate()
    {
        return $this->template;
    }

    public function getVariables()
    {
        return $this->variables;
    }

    public function prepare($template, array $variables = array())
    {
        $this->template = $template;
        $this->variables = $variables;
    }    
}
