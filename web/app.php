<?php

require_once("../vendor/autoload.php");

require '/home/ubuntu/maxime/tmp/vendor/autoload.php';

set_include_path("/home/ubuntu/maxime/tmp/build/classes" . PATH_SEPARATOR . get_include_path());
Propel::init('/home/ubuntu/maxime/tmp/build/conf/demo-conf.php');

include __DIR__ . '/../demo/ApplicationKernel.php';

$application = ApplicationKernel::createInstance();

$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();

$application->getNucleus()
  ->getServiceContainer()
  ->getServiceByName("frontController")
  ->handleRequest($request);
