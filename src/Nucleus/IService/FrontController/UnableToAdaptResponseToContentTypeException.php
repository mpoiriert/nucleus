<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\FrontController;

/**
 * Description of UnableToAdaptResponseToContentTypeException
 *
 * @author Martin
 */
class UnableToAdaptResponseToContentTypeException extends \Exception
{
    public static function formatText(array $contentTypes) 
    {
        return 'The content types [' . implode(',',$contentTypes) . '] cannot be handled by the current application.';
    }
}
