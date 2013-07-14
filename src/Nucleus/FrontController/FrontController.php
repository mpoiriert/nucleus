<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\FrontController;

use Nucleus\IService\Invoker\IInvokerService;
use Nucleus\IService\DependencyInjection\IServiceContainer;
use Symfony\Component\HttpFoundation\Request;
use Nucleus\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use Nucleus\IService\EventDispatcher\IEventDispatcherService;

/**
 * Description of FrontController
 *
 * @author Martin
 */
class FrontController
{
    /**
     * @var \Nucleus\IService\Invoker\IInvokerService 
     */
    private $invoker;

    /**
     * @var \Nucleus\IService\DependencyInjection\IServiceContainer
     */
    private $serviceContainer;

    /**
     * @var \Nucleus\Routing\Router
     */
    private $routing;

    /**
     *
     * @var \Nucleus\IService\FrontController\IResponseAdapter[] 
     */
    private $responseAdapters;

    /**
     *
     * @var \Nucleus\IService\EventDispatcher\IEventDispatcherService 
     */
    private $eventDispatcher;

    /**
     * @param \Nucleus\IService\Invoker\IInvokerService $invoker
     * @param \Nucleus\IService\DependencyInjection\IServiceContainer $serviceContainer
     * @param \Nucleus\Routing\Router $routing
     * 
     * @Inject
     */
    public function initialize(
    IServiceContainer $serviceContainer, IInvokerService $invoker, Router $routing, IEventDispatcherService $eventDispatcher
    )
    {
        $this->invoker = $invoker;
        $this->serviceContainer = $serviceContainer;
        $this->routing = $routing;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Request $request
     */
    public function handleRequest(Request $request)
    {
        $result = $this->routing->match($request->getPathInfo());
        $request->request->add($result);
        $this->execute(
            $result['_service']['name'], $result['_service']['method'], $request
        )->send();
    }

    /**
     * 
     * @param type $serviceName
     * @param type $methodName
     * @param Request $request
     * 
     * @return Response
     */
    public function execute($serviceName, $methodName, Request $request)
    {
        $response = new Response();
        $parameters = array_merge($request->query->all(), $request->request->all());
        $service = $this->serviceContainer->getServiceByName($serviceName);
        $executionResult = $this->invoker->invoke(
            array($service, $methodName), $parameters, array($request, $response)
        );
        $result = array('result' => $executionResult);
        $this->completeResponse($request, $response, $result);
        $response->prepare($request);
        return $response;
    }

    private function completeResponse(Request $request, Response $response, $result)
    {
        foreach ($request->getAcceptableContentTypes() as $contentType) {
            foreach ($this->responseAdapters as $adapter) {
                if ($adapter->adaptResponse($contentType, $request, $response, $result)) {
                    $response->headers->set('Content-Type', $contentType);
                    return;
                }
            }
        }
    }

    /**
     * @param \Nucleus\IService\FrontController\IResponseAdapter[] $adapters
     * 
     * @Inject(adapters="@responseAdapter")
     */
    public function setResponseAdapters(array $adapters = array())
    {
        $this->responseAdapters = $adapters;
    }
}
