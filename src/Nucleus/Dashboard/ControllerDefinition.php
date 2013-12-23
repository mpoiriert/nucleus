<?php

namespace Nucleus\Dashboard;

/**
 * Defines a controller
 *
 * A controller is a class or a service which has actions
 */
class ControllerDefinition
{
    protected $serviceName;

    protected $className;

    protected $name;

    protected $menu;

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
        $this->className = trim($className, '\\');
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
        if ($this->menu === null) {
            $this->menu = ucfirst($name);
        }
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setMenu($menu)
    {
        $this->menu = $menu;
        return $this;
    }

    public function getMenu()
    {
        return $this->menu;
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

    public function getActionsAsMenu()
    {
        $menu = array();
        foreach ($this->getVisibleActions() as $action) {
            $menu[$action->getMenu()] = $action;
        }
        return $menu;
    }

    public function getVisibleActions()
    {
        return array_filter($this->actions, function($a) { 
            return $a->isVisible() && !$a->isAppliedToModel();
        });
    }

    public function getActionsForModel($className)
    {
        return array_filter($this->actions, function($a) use ($className) { 
            return $a->isAppliedToModel() && $a->getAppliedToModel() === $className;
        });
    }
}
