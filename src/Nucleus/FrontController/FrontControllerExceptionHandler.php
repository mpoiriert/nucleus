<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\FrontController;

use Nucleus\IService\FrontController\IExceptionHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;

/**
 * Description of ExceptionHandler
 *
 * @author Martin
 */
class FrontControllerExceptionHandler implements IExceptionHandler
{
    public function handleException(Exception $exception, Request $request, Response $response)
    {
        throw $exception;
    }   
}
