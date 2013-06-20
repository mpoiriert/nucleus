<?php

include __DIR__ . '/../ApplicationKernel.php';

$application = ApplicationKernel::createInstance();

$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();

$application->getNucleus()
  ->getServiceContainer()
  ->getServiceByName("frontController")
  ->handleRequest($request);
