<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Nucleus\CommandLine;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Nucleus\IService\Invoker\IInvokerService;
/**
 * Description of ServiceCommand
 *
 * @author AxelBarbier
 */
class ServiceCommand extends Console\Command\Command 
{
    private $shortDesc;
    private $paramsArray;
    
    private $callable;
    
    /**
     * @var IInvokerService 
     */
    private $invoker;
    /**
     * 
     * @param string $name
     * @param string $shortDesc
     * @param array $paramsArray
     */
    public function __construct($name, $shortDesc, $paramsArray, $serviceCallable){
        //$inputDef           = new CommandLineInputDefinition();
        $this->shortDesc    = $shortDesc;
        $this->paramsArray  = $paramsArray;
        $this->callable     = $serviceCallable;
        parent::__construct($name);
        //$this->setDefinition($inputDef);
    }
    
    public function setInvoker(IInvokerService $invoker)
    {
        $this->invoker = $invoker;
    }
    
    /**
     * Extends configure from Symfony Command Class
     */
    protected function configure()
    {
        $this->setDescription($this->shortDesc);

        foreach($this->paramsArray as $nameParam => $arrayOptions){
            if($arrayOptions['optional']){
                $this->addOption($nameParam, null, Console\Input\InputOption::VALUE_OPTIONAL, $arrayOptions['comment'], null);
            }
            else {
                $this->addOption($nameParam, null, Console\Input\InputOption::VALUE_REQUIRED, $arrayOptions['comment'], null);
            } 
        }
    }
    
    protected function execute(InputInterface $input, OutputInterface $output){
        $arrayOpt = $input->getOptions();
        
        foreach($arrayOpt as $optionName => $optionValue){
            if(is_null($optionValue)){
                unset($arrayOpt[$optionName]);
            }
        }
        $this->invoker->invoke($this->callable, $arrayOpt, array($input,$output));
    }
}

?>
