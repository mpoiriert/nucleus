<?php

namespace Nucleus\Migration;

use \Nucleus\Migration\BaseSqlTask;

class SqlTaskTest extends \PHPUnit_Framework_TestCase
{
    private $configuration;

    public function setUp()
    {
        $this->configuration = array("basePath" => sys_get_temp_dir() . "/");
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testSetConfiguration()
    {
        $sqlTask = new SqlTask();
        $sqlTask->setConfiguration(array());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testPrepareNoFilename()
    {
        $sqlTask = $this->createNewSqlTask();

        $sqlTask->prepare(array());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testPrepareFileDoesntExists()
    {
        $sqlTask = $this->createNewSqlTask();

        $parameters = array("filename" => "abc.sql");

        $sqlTask->prepare($parameters);
    }

    public function testGetUniqueID()
    {
        $sqlTask = $this->createNewSqlTask();

        file_put_contents($this->configuration['basePath'] . "test.sql", "abcd");

        $parameters = array("filename" => "test.sql");
        $sqlTask->prepare($parameters);

        $firstID = $sqlTask->getUniqueID();

        file_put_contents($this->configuration['basePath'] . "test.sql", "1234");

        $secondID = $sqlTask->getUniqueID();

        $this->assertEquals($firstID, $secondID);

        unlink($this->configuration['basePath'] . "test.sql");
    }

    private function createNewSqlTask()
    {
        $sqlTask = new SqlTask();
        $sqlTask->setConfiguration($this->configuration);

        return $sqlTask;
    }
}

class SqlTask extends BaseSqlTask
{
    public function run()
    {
        
    }
}