<?php

namespace Nucleus\Migration;

use Exception;
use Nucleus\IService\Application\IVariableRegistry;
use Nucleus\IService\DependencyInjection\IServiceContainer;
use Nucleus\IService\DependencyInjection\ServiceDoesNotExistsException;
use Nucleus\IService\Migration\IMigrationTask;
use Nucleus\IService\Migration\IMigrator;
use Nucleus\IService\Migration\MigrationTaskNotFoundException;
use RuntimeException;

class Migrator implements IMigrator
{
    private static $variableNamespace = 'migration';
    
    /**
     *
     * @var array
     */
    private $configuration;

    /**
     * @var IServiceContainer 
     */
    private $serviceContainer;

    /**
     *
     * @var IVariableRegistry
     */
    private $applicationVariable;

    /**
     * 
     * @param IServiceContainer $serviceContainer
     * @param IVariableRegistry $applicationVariableRegistry
     * 
     * @Inject
     */
    public function initialize(IServiceContainer $serviceContainer, IVariableRegistry $applicationVariableRegistry)
    {
        $this->serviceContainer = $serviceContainer;
        $this->applicationVariable = $applicationVariableRegistry;
    }

    /**
     * 
     * @param array $configuration
     * 
     * @Inject(configuration="$")
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @\Nucleus\IService\CommandLine\Consolable(name="migration:runAll")
     */
    public function runAll()
    {
        foreach ($this->configuration['versions'] as $version => $tasks) {
            foreach ($tasks as $task) {
                $migrationTask = $this->loadTask($task);
                $id = $version . ":" . $migrationTask->getUniqueId();

                if (!$this->applicationVariable->has($id, self::$variableNamespace)) {
                    $this->runTask($migrationTask, $id);
                }
            }
        }
    }

    /**
     * @\Nucleus\IService\CommandLine\Consolable(name="migration:markAllAsRun")
     */
    public function markAllAsRun()
    {
        foreach ($this->configuration['versions'] as $version => $tasks) {
            foreach ($tasks as $task) {
                $migrationTask = $this->loadTask($task);
                $id = $version . ":" . $migrationTask->getUniqueId();
                $this->applicationVariable->set($id, true, self::$variableNamespace);
            }
        }
    }

    /**
     * 
     * @param string $task Task name
     * @return IMigrationTask of migration task to be executed
     */
    private function loadTask($task)
    {
        $taskName = $task['taskName'];
        try {
            $migrationTask = $this->serviceContainer->getServiceByName('migrationTask.' . $taskName);
            if(!($migrationTask instanceof IMigrationTask)) {
                throw new RuntimeException(
                    'The task [' . $taskName . '] does not implement the [\Nucleus\IService\Migration\IMigrationTask] interface'
                );
            }
            $parameters = array();
            if (isset($task['parameters'])) {
                $parameters = $task['parameters'];
            }
            $migrationTask->prepare($parameters);
            return $migrationTask;
        } catch (ServiceDoesNotExistsException $e) {
            throw new MigrationTaskNotFoundException("MigrationTask [" . $taskName . "] not found", null, $e);
        }
    }

    /**
     * @param IMigrationTask $task
     * @param string $id
     * @throws Exception
     */
    private function runTask(IMigrationTask $task, $id)
    {
        try {
            $task->run();
            $this->applicationVariable->set($id, true, self::$variableNamespace);
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * @param mixed $configuration
     * @return IMigrator
     */
    public static function factory($configuration = null)
    {
        if (is_null($configuration)) {
            $configuration = __DIR__ . '/nucleus.json';
        }

        return Nucleus::serviceFactory($configuration, self::NUCLEUS_SERVICE_NAME);
    }
}