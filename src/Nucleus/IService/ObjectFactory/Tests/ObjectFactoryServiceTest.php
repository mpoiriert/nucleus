<?php

namespace Nucleus\IService\ObjectFactory\Tests;

use \Nucleus\IService\ObjectFactory\IObjectBuilder;

abstract class ObjectFactoryServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \Nucleus\IService\ObjectFactory\IObjectFactoryService
     */
    private $objectFactory;

    /**
     * @return \Nucleus\IService\ObjectFactory\IObjectFactoryService
     */
    abstract protected function getObjectFactory();

    /**
     * @return \Nucleus\IService\ObjectFactory\IObjectFactoryService
     */
    protected function loadObjectFactory()
    {
        if (is_null($this->objectFactory)) {
            $this->objectFactory = $this->getObjectFactory();
            $this->assertInstanceOf('\Nucleus\IService\ObjectFactory\IObjectFactoryService', $this->objectFactory);
        }

        return $this->objectFactory;
    }

    public function testCreateObject()
    {
        $objectFactory = $this->loadObjectFactory();
        $object = $objectFactory->createObject(__NAMESPACE__ . "\TestClass");
        $this->assertInstanceOf(__NAMESPACE__ . "\TestClass", $object);
    }

    public function testCreateObjectWithRegisteredBuilder()
    {
        $objectFactory = $this->loadObjectFactory();

        $objectFactory->registerObjectBuilder(new TestObjectBuilder());
        $object = $objectFactory->createObject(__NAMESPACE__ . "\TestClass");
        $this->assertEquals(1, $object->getInitiliazeCounter(), 'Initialize count should be 1.');

        $objectFactory->registerObjectBuilder(new TestObjectBuilder());
        $objectDoubleInitialize = $objectFactory->createObject(__NAMESPACE__ . "\TestClass");
        $this->assertEquals(2, $objectDoubleInitialize->getInitiliazeCounter(), 'Initialize count should be 2.');
    }
}
if (!class_exists(__NAMESPACE__ . "\TestClass")) {

    class TestClass
    {
        private $initializeCounter = 0;

        public function initialize()
        {
            $this->initializeCounter++;
        }

        public function getInitiliazeCounter()
        {
            return $this->initializeCounter;
        }
    }

    class TestObjectBuilder implements IObjectBuilder
    {

        public function initializeObject($mixed, array $contextParameters = array())
        {
            $mixed->initialize();
        }
    }
}