<?php

namespace Nucleus\DebugBar;

use DebugBar\DebugBar;
use DebugBar\OpenHandler;
use DebugBar\JavascriptRenderer;
use Nucleus\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OpenHandlerController
{
    protected $debugbar;

    protected $openHandler;

    protected $routing;

    /**
     * @param \DebugBar\DebugBar $debugBar
     * 
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function __construct(DebugBar $debugBar, Router $routing)
    {
        $this->debugbar = $debugBar;
        $this->openHandler = new OpenHandler($debugBar);
        $this->routing = $routing;
    }

    public function getUrl()
    {
        return $this->routing->generate('debugbar.openhandler');
    }

    /**
     * @\Nucleus\IService\Routing\Route(name="debugbar.openhandler", path="/nucleus/debugbar")
     */
    public function handle(Request $request, Response $response)
    {
        $data = $this->openHandler->handle($request->query->getIterator(), false, false);
        $response->headers->set('Content-Type', 'application/json', true);
        $response->setContent($data);
        $this->debugbar->setStorage(null);
        return $response;
    }
}