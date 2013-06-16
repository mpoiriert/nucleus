<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Annotation;

use \Doctrine\Common\Annotations\AnnotationRegistry;
use \Doctrine\Common\Annotations\SimpleAnnotationReader;
use \Nucleus\IService\Annotation\IAnnotationParserService;

/**
 * Description of Parser
 *
 * @author mpoirier
 */
class AnnotationParser implements IAnnotationParserService
{
    /**
     * @var \Doctrine\Common\Annotations\SimpleAnnotationReader
     */
    private $reader = null;

    public function __construct(array $configuration = array())
    {
        $this->reader = new SimpleAnnotationReader();
        AnnotationRegistry::registerLoader(function($class) {
                return class_exists($class, true);
            });

        if (!isset($configuration['namespaces'])) {
            $configuration['namespaces'] = array();
        }

        foreach ($configuration['namespaces'] as $namespace) {
            $this->reader->addNamespace($namespace);
        }
    }

    public function addNamespace($namespace)
    {
        $this->reader->addNamespace($namespace);
    }

    /**
     * @param type $class
     * @return \Nucleus\IService\Annotation\IParsingResult 
     */
    public function parse($className)
    {
        $reflectionClass = new \ReflectionClass($className);
        $result = new ParsingResult($reflectionClass->getName());

        $result->setClassAnnotations($this->reader->getClassAnnotations($reflectionClass));
        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            $result->setMethodAnnotations(
                $reflectionMethod->getName(), $this->reader->getMethodAnnotations($reflectionMethod)
            );
        }

        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            $result->setMethodAnnotations(
                $reflectionMethod->getName(), $this->reader->getMethodAnnotations($reflectionMethod)
            );
        }

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $result->setPropertyAnnotations(
                $reflectionProperty->getName(), $this->reader->getPropertyAnnotations($reflectionProperty)
            );
        }

        $parentClass = $reflectionClass->getParentClass();
        if ($parentClass) {
            $parentResult = $this->parse($parentClass->getName());
            $result->mergeParentClass($parentResult);
        }

        $interfaceClasses = $reflectionClass->getInterfaces();
        foreach ($interfaceClasses as $interfaceClass) {
            /* @var $interfaceClass \ReflectionClass  */
            $interfaceResult = $this->parse($interfaceClass->getName());
            $result->mergeParentClass($interfaceResult);
        }

        return $result;
    }
}
