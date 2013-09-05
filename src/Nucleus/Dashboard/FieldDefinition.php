<?php

namespace Nucleus\Dashboard;

use Symfony\Component\Validator\Constraint;

class FieldDefinition
{
    const ACCESS_PROPERTY = 0;
    const ACCESS_GETTER_SETTER = 1;

    protected $property;

    protected $type;

    protected $isArray = false;

    protected $model;

    protected $name;

    protected $description;

    protected $identifier = false;

    protected $optional = false;

    protected $defaultValue;

    protected $accessMethod = 0;

    protected $setterName;

    protected $getterName;

    protected $formFieldType;

    protected $listable = true;

    protected $editable = true;

    protected $link;

    protected $constraints = array();

    protected $defaultFormType = 'text';

    protected $formTypeMapping = array(
        'string' => 'text',
        'int' => 'text',
        'double' => 'text',
        'float' => 'text',
        'bool' => 'checkbox',
        'boolean' => 'checkbox'
    );

    public static function create()
    {
        return new FieldDefinition();
    }

    public function setProperty($name)
    {
        $this->property = $name;
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
        } else {
            $this->model = null;
        }

        if (strpos($type, '[]') !== false) {
            $this->isArray = true;
            $type = rtrim($type, '[]');
        }

        $this->type = $type;

        if ($this->model === null && $this->formFieldType === null) {
            if (isset($this->formTypeMapping[$type])) {
                $this->formFieldType = $this->formTypeMapping[$type];
            } else {
                $this->formFieldType = $this->defaultFormType;
            }
        }

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getFormatedType()
    {
        return $this->type . ($this->isArray ? '[]' : '');
    }

    public function setIsArray($isArray = trye)
    {
        $this->isArray = $isArray;
    }

    public function isArray()
    {
        return $this->isArray;
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
        if ($this->name === null) {
            return $this->property;
        }
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

    public function setAccessMethod($access)
    {
        $this->accessMethod = $access;
        return $this;
    }

    public function getAccessMethod()
    {
        return $this->accessMethod;
    }

    public function isAccessedUsingProperty()
    {
        return $this->accessMethod === self::ACCESS_PROPERTY;
    }

    public function isAccessUsingMethod()
    {
        return $this->accessMethod === self::ACCESS_METHOD;
    }

    public function setGetterSetterMethodNames($getter, $setter)
    {
        $this->getterName = $getter;
        $this->setterName = $setter;
        return $this;
    }

    public function getGetterMethodName()
    {
        if ($this->getterName === null) {
            return 'get' . ucfirst($this->property);
        }
        return $this->getterName;
    }

    public function getSetterMethodName()
    {
        if ($this->setterName === null) {
            return 'set' . ucfirst($this->property);
        }
        return $this->setterName;
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

    public function setConstraints(array $constraints)
    {
        $this->constraints = array();
        array_map(array($this, 'addConstraint'), $this->constraints);
        return $this;
    }

    public function addConstraint(Constraint $constraint)
    {
        $this->constraints[] = $constraint;
    }

    public function getConstraints()
    {
        return $this->constraints;
    }

    /**
     * Returns the field's value from an object
     * 
     * @param object $object
     * @return mixed
     */
    public function getValue($object)
    {
        if ($this->isAccessedUsingProperty()) {
            if (!property_exists($object, $this->property)) {
                return $this->defaultValue;
            }
            return $object->{$this->property};
        }
        return call_user_func(array($object, $this->getGetterMethodName()));
    }

    /**
     * Sets the field's valud on a object
     * 
     * @param object $object
     * @param mixed $value
     */
    public function setValue($object, $value)
    {
        if ($this->isAccessedUsingProperty()) {
            $object->{$this->property} = $value;
            return $this;
        }
        call_user_func(array($object, $this->getSetterMethodName()), $value);
        return $this;
    }
}
