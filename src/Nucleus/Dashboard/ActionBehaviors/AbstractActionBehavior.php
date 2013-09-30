<?php

namespace Nucleus\Dashboard\ActionBehaviors;

abstract class AbstractActionBehavior
{
    protected $params = array();

    protected $action;

    public function setAction($action)
    {
        $this->action = $action;
    }

    public function getAction()
    {
        return $this->action;
    }

    abstract public function getName();

    public function __construct(array $params = array())
    {
        $this->setParams($params);
    }

    public function setParams(array $params)
    {
        $this->params = array_merge($this->params, $params);
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

    public function isInvokable()
    {
        return method_exists($this, 'invoke');
    }

    public function trigger($eventName, $args)
    {
        if (method_exists($this, $eventName)) {
            return call_user_func_array(array($this, $eventName), $args);
        }
    }
}