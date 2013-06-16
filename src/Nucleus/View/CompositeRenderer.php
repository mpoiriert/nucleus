<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\View;

use Nucleus\IService\View\IViewRendererService;
use Nucleus\Framework\Nucleus;

/**
 * Description of CompositeRenderer
 *
 * @author Martin
 */
class CompositeRenderer implements IViewRendererService
{
    /**
     * @var \Nucleus\IService\View\IViewRendererService[] 
     */
    private $renderers = array();

    /**
     * @param string $file
     * @param array $parameters
     * 
     * @return string
     */
    public function render($file, array $parameters = array())
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if ($ext == null) {
            return $this->autoRender($file, $parameters);
        }
        foreach ($this->renderers as $renderer) {
            if ($renderer->canRender($file)) {
                return $renderer->render($file, $parameters);
            }
        }
    }

    private function autoRender($file, array $parameters = array())
    {
        foreach ($this->getExtensions() as $ext) {
            $result = $this->render($file . '.' . $ext, $parameters);
            if ($result) {
                return $result;
            }
        }
    }

    public function canRender($file)
    {
        foreach ($this->renderers as $renderer) {
            if ($renderer->canRender($file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \Nucleus\IService\View\IViewRendererService[] $renderers
     * 
     * @Inject(renderers="@viewRenderer")
     */
    public function setRenderers($renderers)
    {
        $currentObject = $this;
        //We remove the current object since it is tag and we don't want
        //a infinite loop on the method that iterrate trough the renderers
        $this->renderers = array_values(
            array_filter(
                $renderers, function($renderer) use ($currentObject) {
                    return $renderer != $currentObject;
                }
            )
        );
    }

    public function getExtensions()
    {
        $extensions = array();
        foreach ($this->renderers as $renderer) {
            $extensions = array_merge($extensions, $renderer->getExtensions());
        }

        return array_values(array_unique($extensions));
    }

    /**
     * @param mixed $configuration
     * @return IViewRenderer
     */
    public static function factory($configuration = null)
    {
        if (is_null($configuration)) {
            $configuration = __DIR__ . '/nucleus.json';
        }

        return Nucleus::serviceFactory($configuration, 'routing');
    }
}
