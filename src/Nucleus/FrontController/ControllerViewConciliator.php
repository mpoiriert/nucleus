<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\FrontController;

use Nucleus\IService\FrontController\ViewDefinition;

/**
 * Description of ControllerViewLoader
 *
 * @author Martin
 */
class ControllerViewConciliator
{
    /**
     * @var ViewDefinition[]
     */
    private $viewDefinitions = array();
    
    public function setViewDefinition($controller, $name, $template, array $variables)
    {
        $viewDefinition = new ViewDefinition();
        if(!is_null($name)) {
            $viewDefinition->name = $name;
        }
        
        if(!is_null($template)) {
            $viewDefinition->template = $template;
        }
        
        if(!is_null($variables)) {
            $viewDefinition->variables = $variables;
        }
        
        $this->viewDefinitions[$controller] = $viewDefinition;
    }
    
    /**
     * 
     * @param string $controller
     * @return ViewDefinition
     */
    public function getViewDefinition($controller)
    {
        if(!isset($this->viewDefinitions[$controller])) {
            return null;
        }
        
        return $this->viewDefinitions[$controller];
    }
}
