<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\View;

use Nucleus\IService\FrontController\IResponseAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nucleus\IService\View\IViewRendererService;

/**
 * Description of HtmlResponseAdapter
 *
 * @author Martin
 */
class HtmlResponseAdapter implements IResponseAdapter
{
    /**
     * @var Nucleus\IService\View\IViewRendererService
     */
    private $viewRenderer;

    /**
     * @param \Nucleus\IService\View\IViewRendererService $viewRenderer
     * 
     * @Inject
     */
    public function initialize(IViewRendererService $viewRenderer)
    {
        $this->viewRenderer = $viewRenderer;
    }

    public function adaptResponse($contentType, Request $request, Response $response, array $result)
    {
        if ($contentType != "text/html") {
            return false;
        }
        $service = $request->request->get('_service');
        $controller = $service['name'] . '/' . $service['method'];

        if (!$this->viewRenderer->canRender($controller)) {
            return false;
        }

        $response->setContent($this->viewRenderer->render($controller, $result));
        return true;
    }
}