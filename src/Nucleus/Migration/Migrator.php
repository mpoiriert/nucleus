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
use Symfony\Component\Console\Output\ConsoleOutput;

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
     * @var ConsoleOutput
     */
    private $output;

    /**
     * 
     * @param IServiceContainer $serviceContainer
     * @param IVariableRegistry $applicationVariableRegistry
     * 
     * @\Nucleus\IService\DependencyInjection\Inject
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
     * @\Nucleus\IService\DependencyInjection\Inject(configuration="$")
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
                if (!$this->isRun($migrationTask)) {
                    $this->runTask($migrationTask);
                }
            }
        }
    }

    private function markAsRun(IMigrationTask $migrationTask)
    {
        $this->applicationVariable->set($migrationTask->getUniqueId(),true, self::$variableNamespace);
    }

    private function isRun(IMigrationTask $migrationTask)
    {
        return $this->applicationVariable->has($migrationTask->getUniqueId(), self::$variableNamespace);
    }

    /**
     * @\Nucleus\IService\CommandLine\Consolable(name="migration:manual")
     */
    public function manual()
    {
        $this->output = new ConsoleOutput();

        $skipAllRunned = false;
        foreach ($this->configuration['versions'] as $version => $tasks) {
            foreach ($tasks as $task) {
                $migrationTask = $this->loadTask($task);
                if($skipAllRunned && $this->isRun($migrationTask)) {
                    continue;
                }
                do {
                    $invalidChoice = false;
                    switch(strtolower($this->promptTask($migrationTask))) {
                        case 'r':
                            echo "\n";
                            $this->runTask($migrationTask);
                            break;
                        case 's':
                            break;
                        case 'm':
                            $this->markAsRun($migrationTask);
                            break;
                        case 'a':
                            $skipAllRunned = true;
                            break;
                        case 'q':
                            return;
                        default:
                            $invalidChoice = true;
                            echo "\nInvalid choice\n\n";
                            break;
                    }
                } while($invalidChoice);
            }
        }
    }

    private function readline($prompt)
    {
        if (function_exists('readline')) {
            $line = readline($prompt);
        } else {
            $this->output->write($prompt);
            $line = fgets(STDIN, 1024);
            $line = (!$line && strlen($line) == 0) ? false : rtrim($line);
        }

        return $line;
    }

    /**
     * @\Nucleus\IService\CommandLine\Consolable(name="migration:report")
     */
    public function report()
    {
        foreach ($this->configuration['versions'] as $version => $tasks) {
            echo "\n";
            echo "Version: " . $version . "\n";
            echo "--------\n";
            foreach ($tasks as $task) {
                $migrationTask = $this->loadTask($task);
                if($this->isRun($migrationTask)) {
                    echo "  Runned ";
                } else {
                    echo "  To run ";
                }
                echo $this->getTaskFullName($migrationTask) . "\n";
            }
        }
    }

    private function getTaskFullName(IMigrationTask $migrationTask)
    {
        return get_class($migrationTask) . " " . json_encode($migrationTask->getParameters());
    }

    private function promptTask(IMigrationTask $migrationTask)
    {
        $prompt = "\n\nTask: " . $this->getTaskFullName($migrationTask) . "\n";
        if($this->isRun($migrationTask)) {
            $prompt .= "(Already runned)";
        }

        $prompt .= "(R)un,(S)kip,(M)ark As Run,Skip (A)ll Runned,(Q)uit:";

        return $this->readline($prompt);
    }

    /**
     * @\Nucleus\IService\CommandLine\Consolable(name="migration:markAllAsRun")
     */
    public function markAllAsRun()
    {
        foreach ($this->configuration['versions'] as $version => $tasks) {
            foreach ($tasks as $task) {
                $migrationTask = $this->loadTask($task);
                $this->markAsRun($migrationTask);
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
    private function runTask(IMigrationTask $task)
    {
        try {
            $task->run();
            $this->markAsRun($task);
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