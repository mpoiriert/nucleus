<?php

namespace Nucleus\Dashboard;

use Nucleus\Dashboard\ActionBehaviors\AbstractActionBehavior;

class ActionDefinition
{
    const INPUT_CALL = 'call';
    const INPUT_FORM = 'form';
    const INPUT_DYNAMIC = 'dynamic';

    const RETURN_NONE = 'none';
    const RETURN_LIST = 'list';
    const RETURN_OBJECT= 'object';
    const RETURN_FORM = 'form';
    const RETURN_REDIRECT = 'redirect';
    const RETURN_FILE = 'file';
    const RETURN_DYNAMIC = 'dynamic';
    const RETURN_BUILDER = 'builder';
    const RETURN_HTML = 'html';

    const FLOW_NONE = 'none';
    const FLOW_DELEGATE = 'delegate';
    const FLOW_PIPE = 'pipe';
    const FLOW_REDIRECT = 'redirect';
    const FLOW_REDIRECT_WITH_ID = 'redirect_with_id';
    const FLOW_REDIRECT_WITH_DATA = 'redirect_with_data';

    protected $name;

    protected $title;

    protected $icon;

    protected $description;

    protected $default = false;

    protected $menu = true;

    protected $inputType = ActionDefinition::INPUT_CALL;

    protected $inputModel;

    protected $modelOnlyArgument = false;

    protected $loadModel = false;

    protected $returnType = ActionDefinition::RETURN_NONE;

    protected $returnModel;

    protected $flow = ActionDefinition::FLOW_NONE;

    protected $nextAction;

    protected $appliedToModel;

    protected $permissions = array();

    protected $behaviors = array();

    public static function create()
    {
        return new ActionDefinition();
    }

    public function setName($name)
    {
        $this->name = $name;
        if ($this->title === null) {
            $this->title = $name;
        }
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

    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function setDescription($desc)
    {
        $this->description = $desc;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDefault($default)
    {
        $this->default = $default;
        return $this;
    }

    public function isDefault()
    {
        return $this->default;
    }

    public function setMenu($menu = true)
    {
        $this->menu = $menu;
        return $this;
    }

    public function getMenu()
    {
        if ($this->menu === true) {
            return $this->title;
        }
        return trim(rtrim($this->menu, '/') . '/' . $this->title, '/');
    }

    public function providesMenu()
    {
        return $this->menu !== null && !is_bool($this->menu);
    }

    public function isVisible()
    {
        return $this->menu !== false;
    }

    public function setInputType($type)
    {
        $this->inputType = $type;
        return $this;
    }

    public function getInputType()
    {
        return $this->inputType;
    }

    public function setInputModel(ModelDefinition $model)
    {
        $this->inputModel = $model;
        return $this;
    }

    public function getInputModel()
    {
        return $this->inputModel;
    }

    public function setModelOnlyArgument($fieldName)
    {
        $this->modelOnlyArgument = $fieldName;
        return $this;
    }

    public function isModelOnlyArgument()
    {
        return $this->modelOnlyArgument !== false;
    }

    public function getModelArgumentName()
    {
        return $this->modelOnlyArgument;
    }

    public function setLoadModel($load = true)
    {
        $this->loadModel = $load;
        return $this;
    }

    public function isModelLoaded()
    {
        return $this->loadModel;
    }

    public function setReturnType($type)
    {
        $this->returnType = $type;
        return $this;
    }

    public function getReturnType()
    {
        return $this->returnType;
    }

    public function setReturnModel(ModelDefinition $model)
    {
        $this->returnModel = $model;
        return $this;
    }

    public function getReturnModel()
    {
        return $this->returnModel;
    }

    public function setFlow($flow, $nextActionName = null)
    {
        $this->flow = $flow;
        if ($nextActionName !== null) {
            $this->setNextAction($nextActionName);
        }
        if ($flow == self::FLOW_REDIRECT && substr($nextActionName, 0, 1) == '$') {
            $this->returnType = self::RETURN_REDIRECT;
        } else if ($flow !== self::FLOW_NONE) {
            $this->returnType = self::RETURN_FORM;
        }
        return $this;
    }

    public function getFlow()
    {
        return $this->flow;
    }

    public function isFlowing()
    {
        return $this->flow !== self::FLOW_NONE;
    }

    public function setNextAction($actionName)
    {
        if ($this->flow === self::FLOW_NONE) {
            throw new DefinitionBuilderException("Flow must be set to something else than return to set a next action");
        }
        $this->nextAction = $actionName;
        return $this;
    }

    public function getNextAction()
    {
        return $this->nextAction;
    }

    public function applyToModel($className)
    {
        $this->appliedToModel = trim($className, '\\');
        return $this;
    }

    public function isAppliedToModel()
    {
        return $this->appliedToModel !== null;
    }

    public function getAppliedToModel()
    {
        return $this->appliedToModel;
    }

    public function setPermissions(array $perms)
    {
        $this->permissions = $perms;
        return $this;
    }

    public function getPermissions()
    {
        return $this->permissions;
    }

    public function addBehavior(AbstractActionBehavior $behavior)
    {
        $this->behaviors[] = $behavior;
        $behavior->setAction($this);
        return $this;
    }

    public function getBehavior($name)
    {
        foreach ($this->behaviors as $behavior) {
            if ($behavior->getName() === $name) {
                return $behavior;
            }
        }
        return null;
    }

    public function getBehaviors()
    {
        return $this->behaviors;
    }

    public function applyBehaviors($eventName, $args)
    {
        foreach ($this->behaviors as $behavior) {
            $behavior->trigger($eventName, $args);
        }
        return $this;
    }
}
