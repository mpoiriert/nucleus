<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Annotation;

use Nucleus\IService\Annotation\IParsingResult;
use Nucleus\IService\Annotation\NoParsingResultException;

/**
 * Description of ParsingResult
 *
 * @author mpoirier
 */
class ParsingResult implements IParsingResult
{
    private $className = null;
    private $classAnnotations = array();
    private $methodAnnotations = array();
    private $propertyAnnotations = array();
    private $hasAnnotations = false;

    public function __construct($className)
    {
        $this->className = $className;
    }

    public function getParsedClassName()
    {
        return $this->className;
    }

    public function getClassAnnotations(array $filters = array())
    {
        return $this->filters($this->classAnnotations, $filters);
    }

    public function getAllMethodAnnotations(array $filters = array())
    {
        $result = array();
        foreach ($this->methodAnnotations as $methodName => $annotations) {
            $result[$methodName] = $this->filters($annotations, $filters);
        }

        return $result;
    }

    public function getMethodAnnotations($name, array $filters = array())
    {
        if (!array_key_exists($name, $this->methodAnnotations)) {
            throw new NoParsingResultException('The method [' . $name . '] does not exists');
        }

        return $this->filters($this->methodAnnotations[$name], $filters);
    }

    public function getAllPropertyAnnotations(array $filters = array())
    {
        $result = array();
        foreach ($this->propertyAnnotations as $propertyName => $annotations) {
            $result[$propertyName] = $this->filters($annotations, $filters);
        }

        return $result;
    }

    public function getPropertyAnnotations($name, array $filters = array())
    {
        if (!array_key_exists($name, $this->propertyAnnotations)) {
            throw new NoParsingResultException('The property [' . $name . '] does not exists');
        }

        return $this->filters($this->propertyAnnotations[$name], $filters);
    }

    private function filters($annotations, $filters)
    {
        foreach ($filters as $filter) {
            $annotations = array_filter($annotations, $filter);
        }
        return $annotations;
    }

    public function setClassAnnotations($annotations)
    {
        $this->classAnnotations = $annotations;
        $this->hasAnnotations = $this->hasAnnotations || count($annotations) > 0;
    }

    public function setMethodAnnotations($methodName, $annotations)
    {
        $this->methodAnnotations[$methodName] = $annotations;
        $this->hasAnnotations = $this->hasAnnotations || count($annotations) > 0;
    }

    public function setPropertyAnnotations($propertyName, $annotations)
    {
        $this->propertyAnnotations[$propertyName] = $annotations;
        $this->hasAnnotations = $this->hasAnnotations || count($annotations) > 0;
    }

    public function hasAnnotations()
    {
        return $this->hasAnnotations;
    }

    public function getAllAnnotations(array $filters = array())
    {
        $result = array();
        foreach ($this->getClassAnnotations($filters) as $annotation) {
            $result[] = array('context' => 'class', 'annotation' => $annotation, 'contextName' => $this->className);
        }

        foreach ($this->getAllMethodAnnotations($filters) as $methodName => $annotations) {
            foreach ($annotations as $annotation) {
                $result[] = array(
                    'context' => 'method',
                    'annotation' => $annotation,
                    'contextName' => $methodName
                );
            }
        }

        foreach ($this->getAllPropertyAnnotations($filters) as $propertyName => $annotations) {
            foreach ($annotations as $annotation) {
                $result[] = array(
                    'context' => 'property',
                    'annotation' => $annotation,
                    'contextName' => $propertyName
                );
            }
        }

        return $result;
    }

    public function mergeParentClass(ParsingResult $parentResult)
    {
        $this->methodAnnotations = array_merge($parentResult->methodAnnotations, $this->methodAnnotations);
        $this->classAnnotations = array_merge($parentResult->classAnnotations, $this->classAnnotations);
        $this->propertyAnnotations = array_merge($parentResult->propertyAnnotations, $this->propertyAnnotations);
        $this->hasAnnotations = $this->hasAnnotations || $parentResult->hasAnnotations;
    }
}
