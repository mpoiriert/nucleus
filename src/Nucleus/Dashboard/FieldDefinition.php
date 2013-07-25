<?php

namespace Nucleus\Dashboard;

class FieldDefinition
{
    protected $property;

    protected $type;

    protected $model;

    protected $name;

    protected $description;

    protected $identifier = false;

    protected $optional = false;

    protected $defaultValue;

    protected $formFieldType = 'text';

    protected $listable = true;

    protected $editable = true;

    protected $link;

    public function setProperty($name)
    {
        $this->property = $name;
        if ($this->name === null) {
            $this->name = $name;
        }
        return $this;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function setType($type)
    {
        if ($type instanceof ModelDefinition) {
            $this->model = $type;
            $type = $type->getName();
        }
        $this->type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function isModelType()
    {
        return $this->model !== null;
    }

    public function getModel()
    {
        return $this->model;
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

    public function setDescription($desc)
    {
        $this->description = $desc;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setIdentifier($identifier = true)
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function isIdentifier()
    {
        return $this->identifier;
    }

    public function setOptional($optional = true)
    {
        $this->optional = $optional;
        return $this;
    }

    public function isOptional()
    {
        return $this->optional;
    }

    public function setDefaultValue($value)
    {
        $this->defaultValue = $value;
        return $this;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function setFormFieldType($type)
    {
        $this->formFieldType = $type;
        return $this;
    }

    public function getFormFieldType()
    {
        if ($this->model !== null) {
            return $this->model->getIdentifierField()->getFormFieldType();
        }
        return $this->formFieldType;
    }

    public function setListable($listable = true)
    {
        $this->listable = $listable;
        return $this;
    }

    public function isListable()
    {
        return $this->listable;
    }

    public function setEditable($editable = true)
    {
        $this->editable = $editable;
        return $this;
    }

    public function isEditable()
    {
        return $this->editable;
    }

    public function setLink($link)
    {
        $this->link = $link;
        return $this;
    }

    public function getLink()
    {
        return $this->link;
    }
}
