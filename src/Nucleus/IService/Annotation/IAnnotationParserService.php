<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Annotation;

/**
 *
 * @author Martin
 */
interface IAnnotationParserService
{
    /**
     * The service name use as a reference
     */
    const NUCLEUS_SERVICE_NAME = "annotationParser";

    /**
     * 
     * @param type $className
     * 
     * @return \Nucleus\IService\Annotation\IParsingResult
     */
    public function parse($className);
}