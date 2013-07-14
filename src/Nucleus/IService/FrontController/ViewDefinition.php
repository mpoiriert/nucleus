<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\FrontController;

/**
 * Description of ViewDefinition
 *
 * @author Martin
 * 
 * @Annotation
 */
class ViewDefinition
{
    /**
     * The name of the view that will be used.
     * 
     * @var string
     */
    public $name = 'default';
    
    /**
     * The file path if needed for the view. Dependent of the view
     * defined in name it might not be used
     * 
     * @var string
     */
    public $template;
    
    /**
     * Those variables will be merge with the return value
     * of a execution
     * 
     * @var array
     */
    public $variables = array();
}
