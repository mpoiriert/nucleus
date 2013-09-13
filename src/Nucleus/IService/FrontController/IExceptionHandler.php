<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\FrontController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;

/**
 * Description of IExceptionHandler
 *
 * @author Martin
 */
interface IExceptionHandler
{
    const NUCLEUS_SERVICE_NAME = "frontControllerExceptionHandler";
    
    /**
     * Handle if a exception is thrown during execution process
     * 
     * @param Exception $exception
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     * 
     * @return Response|null
     */
    public function handleException(Exception $exception, Request $request, Response $response);
}
