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
 * @\Nucleus\IService\DependencyInjection\Tag(name="templateRenderer")
 */
interface ITemplateRendererService
{
    /**
     * The service name use as a reference
     */
    const NUCLEUS_SERVICE_NAME = 'templateRenderer';

    /**
     * @param string $template
     * @param array $variables
     * 
     * @return string
     */
    public function render($template, array $variables = array());

    /**
     * Check if the render is able to render this specific file
     * 
     * @param string $template
     * 
     * @return boolean
     */
    public function canRender($template);

    /**
     * @return array
     */
    public function getExtensions();
}
