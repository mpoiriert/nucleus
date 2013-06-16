<?php

require_once(__DIR__ . '/../vendor/autoload.php');

$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();


\Nucleus\Framework\Nucleus::factory(__DIR__ . '/../nucleus.json')
  ->getServiceContainer()
  ->getServiceByName("frontController")
  ->handleRequest($request);