<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\FrontController;

use Nucleus\IService\FrontController\IResponseAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of JsonResponseAdapter
 *
 * @author Martin
 */
class JsonResponseAdapter implements IResponseAdapter
{

    public function adaptResponse($contentType, Request $request, Response $response, array $result)
    {
        if ($contentType != "application/json") {
            return false;
        }

        $response->setContent(json_encode(iterator_to_array_recursive($result)));
        return true;
    }
}
