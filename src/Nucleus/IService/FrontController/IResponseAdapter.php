<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\FrontController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @author Martin
 * 
 * @\Nucleus\IService\DependencyInjection\Tag(name="responseAdapter")
 */
interface IResponseAdapter
{

    /**
     * @param string $contentType
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     * @param array $result
     * 
     * @return boolean true if the response have been adapted or false if not
     */
    public function adaptResponse($contentType, Request $request, Response $response, array $result);
}
