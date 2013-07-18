<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Nucleus\CommandLine;

use Nucleus\Framework\Nucleus;
use Nucleus\IService\CommandLine\ICommandLineService;
use Symfony\Component\Console\Application;
use Nucleus\IService\Invoker\IInvokerService;
use Nucleus\IService\DependencyInjection\IServiceContainer;

/**
 * Description of CommandLine
 *
 * @author AxelBarbier
 */
class CommandLine extends Application implements ICommandLineService  {

    /**
     * @var IServiceContainer $serviceContainer
     */
    private $serviceContainer;
    /**
     *
     * @var IInvokerService $invoker 
     */
    private $invoker;
    
    /**
     * 
     * @param type $name
     * @param type $version
     * @param \Nucleus\IService\Invoker\IInvokerService $invoker
     * @param \Nucleus\IService\DependencyInjection\IServiceContainer $serviceContainer
     * @Inject
     */
    public function __construct($name = 'Nucleus', $version = '1.0', IInvokerService $invoker, IServiceContainer $serviceContainer) 
    {
        // @TODO : get version and name from application Kernel
        parent::__construct($name, $version);
        $this->serviceContainer = $serviceContainer;
        $this->invoker = $invoker;
    }
   
    
    /**
     * @param string $name
     * @param string $serviceName
     * @param string $methodName
     * @param string $shortDesc
     * @param array $paramsMethod
     */
    public function addCommand($name, $serviceName, $methodName,$shortDesc, $paramsMethod)
    {
        $serviceCallable = $this->serviceContainer->getServiceByName($serviceName);
        $command = new ServiceCommand($name, $shortDesc, $paramsMethod, array($serviceCallable, $methodName));
        $command->setInvoker($this->invoker);
        $this->add($command);
    }
    
    /**
     * @param mixed $configuration
     * @return ICommandLineService
     */
    public static function factory($configuration = null)
    {
        if (is_null($configuration)) {
            $configuration = __DIR__ . '/nucleus.json';
        }

        return Nucleus::serviceFactory($configuration, ICommandLineService::NUCLEUS_SERVICE_NAME);
    }
}

