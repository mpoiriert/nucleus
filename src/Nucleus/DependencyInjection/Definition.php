<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\DependencyInjection;

use Symfony\Component\DependencyInjection\Definition as BaseDefinition;

/**
 * Description of Definition
 *
 * @author Martin
 */
class Definition extends BaseDefinition
{
    private static $codeInitializationDecorator = '  
  $serviceContainer = $this;
  $service = $instance;
  $configurator = function() use ($serviceContainer, $service) {
    %code%
  };
  $configurator';
    private $codeInitialization = '';

    public function addCodeInitialization($code)
    {
        $this->codeInitialization .= $code;
        $this->updateConfigurator();
    }

    public function getCodeInitalization()
    {
        return $this->codeInitialization;
    }

    public function setCodeInitialization($code)
    {
        $this->codeInitialization = $code;
        $this->updateConfigurator();
    }

    private function updateConfigurator()
    {
        $this->setConfigurator(str_replace('%code%', $this->codeInitialization, self::$codeInitializationDecorator));
    }
}
