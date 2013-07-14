<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\View;

/**
 * Description of IView
 *
 * @author Martin
 * 
 */
interface IView
{
    public function prepare($template, array $variables = array());
	
    public function getVariables();
	
    public function getTemplate();
}
