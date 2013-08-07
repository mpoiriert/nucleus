<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\View;

use Nucleus\IService\View\ITemplateRendererService;
use Nucleus\Framework\Nucleus;

/**
 * Description of CompositeRenderer
 *
 * @author Martin
 */
class CompositeTemplateRenderer implements ITemplateRendererService
{
    /**
     * @var ITemplateRendererService[] 
     */
    private $renderers = array();

    /**
     * @param string $template
     * @param array $parameters
     * 
     * @return string
     */
    public function render($template, array $variables = array())
    {
        $ext = pathinfo($template, PATHINFO_EXTENSION);
        if ($ext == null) {
            return $this->autoRender($template, $variables);
        }
        foreach ($this->renderers as $renderer) {
            if ($renderer->canRender($template)) {
                return $renderer->render($template, $variables);
            }
        }
    }

    private function autoRender($template, array $variables = array())
    {
        foreach ($this->getExtensions() as $ext) {
            $result = $this->render($template . '.' . $ext, $variables);
            if ($result) {
                return $result;
            }
        }
    }

    public function canRender($template)
    {
        foreach ($this->renderers as $renderer) {
            if ($renderer->canRender($template)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ITemplateRendererService[] $renderers
     * 
     * @\Nucleus\IService\DependencyInjection\Inject(renderers="@templateRenderer")
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
     * @return ITemplateRenderer
     */
    public static function factory($configuration = null)
    {
        if (is_null($configuration)) {
            $configuration = __DIR__ . '/nucleus.json';
        }

        return Nucleus::serviceFactory($configuration, ITemplateRendererService::NUCLEUS_SERVICE_NAME);
    }
}
