<?php

require_once __DIR__ . "/../vendor/autoload.php";
include __DIR__ . '/../demo/ApplicationKernel.php';

$application = ApplicationKernel::createInstance();


$consoleApplication = $application->getNucleus()
                        ->getServiceContainer()
                        ->getServiceByName("console");

$consoleApplication->run();
  
