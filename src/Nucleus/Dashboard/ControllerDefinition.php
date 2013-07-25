<?php

namespace Nucleus\Dashboard;

class ControllerDefinition
{
    protected $serviceName;

    protected $className;

    protected $name;

    protected $title;

    protected $actions = array();

    public function setServiceName($serviceName)
    {
        $this->serviceName = $serviceName;
        if ($this->name === null) {
            $this->name = $this->serviceName;
        }
        return $this;
    }

    public function getServiceName()
    {
        return $this->serviceName;
    }

    public function setClassName($className)
    {
        $this->className = $className;
        if ($this->name === null) {
            $this->name = basename(str_replace('\\', '/', $this->className));
        }
        return $this;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setActions(array $actions)
    {
        $this->actions = array();
        array_map(array($this, 'addAction'), $actions);
        return $this;
    }

    public function addAction(ActionDefinition $action)
    {
        $this->actions[] = $action;
        return $this;
    }

    public function getAction($name)
    {
        foreach ($this->actions as $action) {
            if ($action->getName() === $name) {
                return $action;
            }
        }
        return false;
    }

    public function getDefaultAction()
    {
        foreach ($this->actions as $action) {
            if ($action->isDefault()) {
                return $action;
            }
        }
        return $this->actions[0];
    }

    public function getActions()
    {
        return $this->actions;
    }

    public function getVisibleActions()
    {
        return array_filter($this->actions, function($a) { return $a->isVisible(); });
    }
}
