<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Nucleus\CommandLine\Tests;

use Nucleus\CommandLine\CommandLine;
use Nucleus\Framework\Nucleus;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Console\Tester\CommandTester;
/**
 * Description of CommandLineTest
 *
 * @author AxelBarbier
 */
class CommandLineTest extends PHPUnit_Framework_TestCase
{
    /*
     * @var ICommandLineService
     */
    private $consolableService;
    private $console;
    private $nucleus;
    
    public function setUp()
    {
        $this->nucleus = Nucleus::factory(__DIR__ . '/nucleus.json');
    }
    
    public function loadConsolableService(){
        if (is_null($this->consolableService)) {
            $serviceContainer        = $this->nucleus->getServiceContainer();
            $this->consolableService = $serviceContainer->getServiceByName("commandLineServiceTest");
            $this->console = $serviceContainer->getServiceByName("console");
            $this->assertInstanceOf('\Nucleus\CommandLine\CommandLine', $this->console);
        }

        return $this->console;
    }
    
    public function testCommandLineWithName(){
        $applicationConsole = $this->loadConsolableService();
        
        ob_start();
        $command            = $applicationConsole->find('test');
        $commandTester      = new CommandTester($command);
        $commandTester->execute(
                array('command' => $command->getName()
                    )
                );
        $retCommand = $commandTester->getDisplay();
        $test = ob_get_contents();
        ob_clean();
        $this->assertRegExp('/commandLineWithName/', $test);
    }
   
}