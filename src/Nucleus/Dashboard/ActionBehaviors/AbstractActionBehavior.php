<?php

namespace Nucleus\Dashboard\ActionBehaviors;

abstract class AbstractActionBehavior
{
    protected $params = array();

    abstract public function getName();

    public function __construct(array $params = array())
    {
        $this->setParams($params);
    }

    public function setParams(array $params)
    {
        $this->params = $params;
        return $this;
    }

    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
        return $this;
    }

    public function getParam($name, $default = null)
    {
        if (array_key_exists($name, $this->params)) {
            return $this->params[$name];
        }
        return $default;
    }

    public function getParams()
    {
        return $this->params;
    }
}