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
     * @param ITemplateRendererService $templateRenderer
     * 
     * @Inject
     */
    public function initialize(ITemplateRendererService $templateRenderer)
    {
        $this->templateRenderer = $templateRenderer;
    }

    public function adaptResponse($contentType, Request $request, Response $response, array $result)
    {
        if ($contentType != "text/html") {
            return false;
        }
        $service = $request->request->get('_service');
        $controller = $service['name'] . '/' . $service['method'];

        if (!$this->templateRenderer->canRender($controller)) {
            return false;
        }

        $response->setContent($this->templateRenderer->render($controller, $result));
        return true;
    }
}