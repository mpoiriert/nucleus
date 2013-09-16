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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Nucleus\IService\EventDispatcher\IEventDispatcherService;
use Nucleus\IService\FrontController\IExceptionHandler;
use Nucleus\IService\FrontController\Error404Exception;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Exception;

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
     * The list of default content types to use when client accept all type
     * 
     * @var array
     */
    private $defaultAcceptableContentTypes = array('text/html');
    
    /**
     * @var IExceptionHandler 
     */
    private $frontControllerExceptionHandler;

    /**
     * @param \Nucleus\IService\Invoker\IInvokerService $invoker
     * @param \Nucleus\IService\DependencyInjection\IServiceContainer $serviceContainer
     * @param \Nucleus\Routing\Router $routing
     * 
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function __construct(
        IServiceContainer $serviceContainer, 
        IInvokerService $invoker, 
        Router $routing, 
        IEventDispatcherService $eventDispatcher,
        IExceptionHandler $frontControllerExceptionHandler
    )
    {
        $this->invoker = $invoker;
        $this->serviceContainer = $serviceContainer;
        $this->frontControllerExceptionHandler = $frontControllerExceptionHandler;
        $this->routing = $routing;
        $this->eventDispatcher = $eventDispatcher;
    }
    
    /**
     * This is to set the order when the accepted content type is define by
     * the all content type string 
     * 
     * @param string[] $contentTypes
     */
    public function setDefaultAcceptableContentTypes(array $contentTypes)
    {
        $this->defaultAcceptableContentTypes = $contentTypes;
    }

    /**
     * @param Request $request
     */
    public function handleRequest(Request $request)
    {
        $this->routing->setCurrentRequest($request);
        
        $response = new Response();
        
        try {
            $result = $this->routing->match($request->getPathInfo());
            $request->request->add($result);
        } catch (ResourceNotFoundException $e) {
            $exception = new Error404Exception($e);
            $executionResult = $this->frontControllerExceptionHandler->handleException(
                $exception, 
                $request, 
                $response
            );
            $this->completeResponse($executionResult, $request, $response)->send();
            return;
        }
        
        $this->execute(
            $result['_service']['name'], 
            $result['_service']['method'], 
            $request, 
            $response
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
    public function execute($serviceName, $methodName, Request $request, Response $response = null)
    {
        $this->routing->setCurrentRequest($request);
        if(is_null($response)) {
            $response = new Response();
        }
        
        $parameters = array_merge($request->query->all(), $request->request->all());
        $service = $this->serviceContainer->getServiceByName($serviceName);
        try {
            $executionResult = $this->invoker->invoke(
                array($service, $methodName), $parameters, array($request, $response)
            );
        } catch(Exception $e) {
            $executionResult = $this->frontControllerExceptionHandler->handleException($e, $request, $response);
        }
        
        $finalResponse = $this->completeResponse($executionResult, $request, $response);
        $finalResponse->prepare($request);
        return $finalResponse;
    }

    /**
     * @param mixed $executionResult
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    private function completeResponse($executionResult, Request $request, Response $response)
    {
        if($executionResult instanceof RedirectResponse){
            return $executionResult;
        }
        
        $result = array('result' => $executionResult);
        if($response->getContent()) {
           return $response;
        }
        $contentTypes = $request->getAcceptableContentTypes();
        $this->processContentTypes($contentTypes, $request, $response, $result);
        return $response;
    }
    
    private function processContentTypes($contentTypes, Request $request, Response $response, $result) 
    {
        foreach($contentTypes as $contentType) {
            foreach ($this->responseAdapters as $adapter) {
                if($contentType == '*/*') {
                    //We remove the */* content type so we don't have a infinite loop
                    $defaultContentTypes = array_diff($this->defaultAcceptableContentTypes,array('*/*'));
                    if($this->processContentTypes($defaultContentTypes, $request, $response, $result)) {
                        return true;
                    }
                }
                if ($adapter->adaptResponse($contentType, $request, $response, $result)) {
                    $response->headers->set('Content-Type', $contentType);
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * @param \Nucleus\IService\FrontController\IResponseAdapter[] $adapters
     * 
     * @\Nucleus\IService\DependencyInjection\Inject(adapters="@responseAdapter")
     */
    public function setResponseAdapters(array $adapters = array())
    {
        $this->responseAdapters = $adapters;
    }
}
