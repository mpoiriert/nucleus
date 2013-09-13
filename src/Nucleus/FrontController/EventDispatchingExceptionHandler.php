<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\FrontController;

use Nucleus\IService\FrontController\IExceptionHandler;
use Nucleus\IService\EventDispatcher\IEventDispatcherService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;

/**
 * Description of EventDispatchingExceptionHandler
 *
 * @author Martin
 */
class EventDispatchingExceptionHandler implements IExceptionHandler
{
    private $eventDispatcher;
    
    /**
     * @\Nucleus\IService\DependencyInjection\Inject
     * 
     * @param IEventDispatcherService $eventDispatcher
     */
    public function __construct(IEventDispatcherService $eventDispatcher)
    {
       $this->eventDispatcher = $eventDispatcher; 
    }

    public function handleException(Exception $exception, Request $request, Response $response)
    {
        $this->eventDispatcher->dispatch(
            'Exception.thrownFromFrontController',
            $exception,
            array('request'=>$request,'response'=>$response)
        );
        return $exception;
    }
}
