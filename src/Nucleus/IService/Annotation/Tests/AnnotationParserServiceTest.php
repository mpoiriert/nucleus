<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\Annotation\Tests;

use Nucleus\IService\Annotation\NoParsingResultException;

/**
 * Description of AnnotationParserTest
 *
 * @author Martin
 */
abstract class AnnotationParserServiceTest extends \PHPUnit_Framework_TestCase
{
    private $annotationParserService;

    /**
     * @return \Nucleus\IService\Annotation\IAnnotationParserService
     */
    abstract protected function getAnnotationParserService($configuration);

    /**
     * @return \Nucleus\IService\Annotation\IAnnotationParserService
     */
    private function loadAnnotationParserService()
    {
        if (is_null($this->annotationParserService)) {
            $this->annotationParserService = $this->getAnnotationParserService(array('namespaces' => array(__NAMESPACE__)));
            $this->assertInstanceOf('\Nucleus\IService\Annotation\IAnnotationParserService', $this->annotationParserService);
        }

        return $this->annotationParserService;
    }

    public function providerTestParseMethod()
    {
        $annotationClass = __NAMESPACE__ . '\TestAnnotation';
        $expectedAnnotations[] = $annotationClass;

        return array(
            //Parent Class
            array(__NAMESPACE__ . '\TestAnnotedParent', $expectedAnnotations, 'annoted', $expectedAnnotations),
            array(__NAMESPACE__ . '\TestAnnotedParent', $expectedAnnotations, 'notAnnoted', array()),
            array(__NAMESPACE__ . '\TestAnnotedParent', $expectedAnnotations, 'annotedByOverride', array()),
            //Child Class
            array(__NAMESPACE__ . '\TestAnnotedChild', $expectedAnnotations, 'annoted', $expectedAnnotations),
            array(__NAMESPACE__ . '\TestAnnotedChild', $expectedAnnotations, 'notAnnoted', array()),
            array(__NAMESPACE__ . '\TestAnnotedChild', $expectedAnnotations, 'annotedByOverride', $expectedAnnotations),
            array(__NAMESPACE__ . '\TestAnnotedChild', $expectedAnnotations, 'doubleAnnotated', array_fill(0, 2, $annotationClass)),
        );
    }

    /**
     * @dataProvider providerTestParseMethod
     * @param type $class
     * @param type $expectedClassAnnotations
     * @param type $method
     * @param type $expectedMethodAnnotations
     */
    public function testParseMethod($class, $expectedClassAnnotations, $method, $expectedMethodAnnotations)
    {
        $service = $this->loadAnnotationParserService();

        $result = $service->parse($class);

        $this->assertTrue($result->hasAnnotations());

        $this->assertInstanceOf('Nucleus\IService\Annotation\IParsingResult', $result);

        $this->assertEquals($result->getParsedClassName(), $class);

        $classAnnotations = $result->getClassAnnotations();
        $this->validateAnnotation($expectedClassAnnotations, $classAnnotations);

        $methodAnnotations = $result->getMethodAnnotations($method);
        $this->validateAnnotation($expectedMethodAnnotations, $methodAnnotations);
    }

    public function testParseClassImplement()
    {
        $service = $this->loadAnnotationParserService();

        $result = $service->parse(__NAMESPACE__ . '\TestAnnotedClassImplement');
        $this->assertInstanceOf('Nucleus\IService\Annotation\IParsingResult', $result);

        $classAnnotations = $result->getClassAnnotations();

        $this->assertEquals(2, count($classAnnotations));

        usort($classAnnotations, function($a, $b) {
                return strcmp(get_class($a), get_class($b));
            });

        $this->assertInstanceOf(__NAMESPACE__ . '\TestAnnotation', $classAnnotations[0]);
        $this->assertInstanceOf(__NAMESPACE__ . '\TestInterfaceAnnotation', $classAnnotations[1]);
    }

    private function validateAnnotation($expectedClassAnnotations, $resultAnnotations)
    {
        $this->assertSameSize($expectedClassAnnotations, $resultAnnotations);
        foreach ($resultAnnotations as $index => $annotation) {
            $this->assertInstanceOf($expectedClassAnnotations[$index], $annotation);
        }
    }

    public function testParseProperty()
    {
        $service = $this->loadAnnotationParserService();

        $result = $service->parse(__NAMESPACE__ . '\TestAnnotedProperty');
        $this->assertInstanceOf('Nucleus\IService\Annotation\IParsingResult', $result);

        $propertyAnnotations = $result->getAllPropertyAnnotations();

        $this->assertArrayHasKey('property', $propertyAnnotations);
        $this->assertCount(1, $propertyAnnotations['property']);

        $this->assertInstanceOf(__NAMESPACE__ . '\TestAnnotation', $propertyAnnotations['property'][0]);

        $propertyAnnotation = $result->getPropertyAnnotations('property');
        $this->assertCount(1, $propertyAnnotation);
        $this->assertInstanceOf(__NAMESPACE__ . '\TestAnnotation', $propertyAnnotation[0]);
    }

    public function testNoParsingResultException()
    {
        $service = $this->loadAnnotationParserService();
        $result = $service->parse(__NAMESPACE__ . '\TestAnnotation');

        try {
            $result->getMethodAnnotations('doesNotExists');
            $this->fail('Getting annotations of a not existing method should throw a exception');
        } catch (NoParsingResultException $e) {
            $this->assertTrue(true);
        }

        try {
            $result->getPropertyAnnotations('doesNotExists');
            $this->fail('Getting annotations of a not existing property should throw a exception');
        } catch (NoParsingResultException $e) {
            $this->assertTrue(true);
        }
    }
}
if (!class_exists(__NAMESPACE__ . '\TestAnnotation')) {

    /**
     * @Annotation
     */
    class TestAnnotation
    {
        
    }

    /**
     * @Annotation
     */
    class TestInterfaceAnnotation
    {
        
    }

    /**
     * @TestAnnotation
     */
    class TestAnnotedParent
    {

        public function notAnnoted()
        {
            
        }

        /**
         * @TestAnnotation
         */
        public function annoted()
        {
            
        }

        public function annotedByOverride()
        {
            
        }
    }

    class TestAnnotedChild extends TestAnnotedParent
    {

        /**
         * @TestAnnotation
         */
        public function annotedByOverride()
        {
            
        }

        /**
         * @TestAnnotation
         * @TestAnnotation
         */
        public function doubleAnnotated()
        {
            
        }
    }

    /**
     * @TestInterfaceAnnotation
     */
    interface TestAnnotatedInterface
    {
        
    }

    /**
     * @TestAnnotation
     */
    class TestAnnotedClassImplement implements TestAnnotatedInterface
    {
        
    }

    class TestAnnotedProperty
    {
        /**
         * @TestAnnotation
         * @var type 
         */
        public $property;

    }
}
