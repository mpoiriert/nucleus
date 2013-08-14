<?php

namespace Nucleus\Dashboard;

class ActionDefinition
{
    const INPUT_CALL = 'call';
    const INPUT_FORM = 'form';
    const RETURN_NONE = 'none';
    const RETURN_LIST = 'list';
    const RETURN_OBJECT= 'object';
    const RETURN_FORM = 'form';

    protected $name;

    protected $title;

    protected $icon;

    protected $description;

    protected $default = false;

    protected $visible = true;

    protected $inputType = 'call';

    protected $inputModel;

    protected $modelOnlyArgument = false;

    protected $loadModel = false;

    protected $returnType = 'none';

    protected $returnModel;

    protected $pipe;

    protected $appliedToModel;

    protected $permissions = array();

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

    public function setVisible($visible = true)
    {
        $this->visible = $visible;
        return $this;
    }

    public function isVisible()
    {
        return $this->visible;
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

    public function setPipe($pipe)
    {
        $this->pipe = $pipe;
        $this->returnType = self::RETURN_FORM;
        return $this;
    }

    public function isPiped()
    {
        return $this->pipe !== null;
    }

    public function getPipe()
    {
        return $this->pipe;
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
    }

    public function getPermissions()
    {
        return $this->permissions;
    }
}
