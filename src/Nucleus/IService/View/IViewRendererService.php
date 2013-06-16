<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\View;

/**
 * Description of IRenderer
 *
 * @author Martin
 * 
 * @Tag("viewRenderer")
 */
interface IViewRendererService
{
    /**
     * The service name use as a reference
     */
    const NUCLEUS_SERVICE_NAME = 'viewRenderer';

    /**
     * @param type $file
     * @param array $parameters
     * 
     * @return string
     */
    public function render($file, array $parameters = array());

    /**
     * Check if the render is able to render this specific file
     * 
     * @param string $file
     * 
     * @return boolean
     */
    public function canRender($file);

    /**
     * @return array
     */
    public function getExtensions();
}
