<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\View;

/**
 * Description of HtmlRenderer
 *
 * @author Martin
 */
class HtmlTemplateRenderer extends BaseExtensionRenderer
{

    public function __construct()
    {
        $this->setExtensions(array('html', 'htm'));
    }

    public function render($file, array $parameters = array())
    {

        extract($parameters);

        ob_start();

        try {
            include $this->getFileSystemLoader()->getFullPath($file);
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean();
    }
}
