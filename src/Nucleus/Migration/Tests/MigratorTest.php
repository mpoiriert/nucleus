<?php

namespace Nucleus\Migration\Tests;

use Nucleus\Framework\Nucleus;
use Nucleus\Migration\BaseMigrationTask;
use PHPUnit_Framework_TestCase;

class MigratorTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     * @var type 
     */
    private $migrator;
    private $serviceContainer;

    protected function initializeContext($file)
    {
        $this->serviceContainer = Nucleus::factory(__DIR__ . '/fixtures/' . $file)->getServiceContainer();

        $this->migrator = $this->serviceContainer->getServiceByName('migrator');
    }

    public function testRun()
    {
        $this->initializeContext("run.json");

        $testTask = $this->serviceContainer->getServiceByName("migrationTask.testTask");
        /* @var $testTask TestTask */

        $this->assertEquals(0, $testTask->amount);

        $this->migrator->runAll();

        $this->assertEquals(1, $testTask->amount);

        $this->assertEquals(array('filename' => 'update.sql'), $testTask->parameters);

        $this->migrator->runAll();

        //It should not be runned twice
        $this->assertEquals(1, $testTask->amount);
    }

    /**
     * @expectedException \Nucleus\IService\Migration\MigrationTaskNotFoundException
     */
    public function testRunMigrationTaskNotFound()
    {
        $this->initializeContext("runMigrationTaskNotFound.json");

        $this->migrator->runAll();
    }

    public function testMarkAllAsRun()
    {
        $this->initializeContext("markAllAsRun.json");

        $testTask = $this->serviceContainer->getServiceByName("migrationTask.testTask");
        /* @var $testTask TestTask */

        $testTask2 = $this->serviceContainer->getServiceByName("migrationTask.testTask2");
        /* @var $testTask2 TestTask */

        $this->assertEquals(array(0, 0), array($testTask->amount, $testTask2->amount));

        $this->migrator->markAllAsRun();
        $this->migrator->runAll();

        $this->assertEquals(array(0, 0), array($testTask->amount, $testTask2->amount));
    }

    /**
     * @expectedException \Nucleus\IService\Migration\MigrationTaskNotFoundException
     */
    public function testMarkAllAsRunMigrationTaskNotFound()
    {
        $this->initializeContext("markAllAsRunMigrationTaskNotFound.json");

        $this->migrator->markAllAsRun();
    }
}

class TestTask extends BaseMigrationTask
{
    public $parameters;
    public $amount = 0;
    public $uniqueId;

    public function __construct()
    {
        $this->uniqueId = uniqid();
    }
    
    public function prepare(array $parameters)
    {
        $this->parameters = $parameters;
    }

    public function run()
    {
        $this->amount++;
    }

    public function getUniqueID()
    {
        return $this->uniqueId;
    }
}