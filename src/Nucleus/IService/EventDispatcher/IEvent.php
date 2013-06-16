<?php

namespace Nucleus\IService\EventDispatcher;

interface IEvent
{

    public function getName();

    public function getSubject();

    public function getDispatcher();

    public function isPropagationStopped();

    public function stopPropagation();

    public function getParameters();

    public function hasParameter($name);

    public function getParameter($name);
}