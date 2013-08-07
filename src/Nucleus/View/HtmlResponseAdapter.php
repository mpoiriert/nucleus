<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\View;

use Nucleus\IService\FrontController\IResponseAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nucleus\IService\View\ITemplateRendererService;
use Nucleus\FrontController\ControllerViewConciliator;
use Nucleus\IService\View\IViewConciliator;

/**
 * Description of HtmlResponseAdapter
 *
 * @author Martin
 */
class HtmlResponseAdapter implements IResponseAdapter
{
    /**
     * @var IViewRendererService
     */
    private $templateRenderer;
    
    /**
     *
     * @var ControllerViewConciliator 
     */
    private $controllerViewConciliator;
    
    /**
     *
     * @var IViewConciliator
     */
    private $viewConciliator;

    /**
     * @param ITemplateRendererService $templateRenderer
     * 
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function initialize(ITemplateRendererService $templateRenderer)
    {
        $this->templateRenderer = $templateRenderer;
    }
    
    /**
     * 
     * @param IViewConciliator $viewConciliator
     * @param ControllerViewConciliator $controllerViewConciliator
     * 
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function setViewConciliator(IViewConciliator $viewConciliator = null, ControllerViewConciliator $controllerViewConciliator = null)
    {
        $this->viewConciliator = $viewConciliator;
        $this->controllerViewConciliator = $controllerViewConciliator;
    }

    public function adaptResponse($contentType, Request $request, Response $response, array $result)
    {
        if ($contentType != "text/html") {
            return false;
        }
        $service = $request->request->get('_service');
        $controller = $service['name'] . '/' . $service['method'];

        if($this->adaptFromConciliator($controller, $request, $response, $result)) {
           return true; 
        }

        return $this->adaptFromTemplateRenderer($controller, $request, $response, $result);
    }
    
    private function adaptFromConciliator($controller, Request $request, Response $response, array $result)
    {
        if(is_null($this->viewConciliator) || is_null($this->controllerViewConciliator)) {
            return false;
        }
        
        $viewDefinition = $this->controllerViewConciliator->getViewDefinition($controller);
        
        if(is_null($viewDefinition)) {
            return false;
        }
        
        if(!$this->viewConciliator->hasView($viewDefinition->name)) {
            return false;
        }

        $view = $this->viewConciliator->getView($viewDefinition->name);
        
        $view->prepare(
            $viewDefinition->template, 
            array_merge($viewDefinition->variables,$result)
        );
        
        return $this->adaptFromTemplateRenderer(
            $view->getTemplate(),
            $request, 
            $response, 
            $view->getVariables()
        );
    }
    
    private function adaptFromTemplateRenderer($template, Request $request, Response $response, array $result)
    {
        if (!$this->templateRenderer->canRender($template)) {
            return false;
        }
        
        $response->setContent($this->templateRenderer->render($template, $result));
        return true;
    }
}