<?php

namespace Nucleus\Dashboard;

use Symfony\Component\Validator\Constraint;

/**
 * A field is a property of a model
 */
class FieldDefinition
{
    /**
     * Access the data using an object property
     */
    const ACCESS_PROPERTY = 0;
    /**
     * Access the data using a method
     */
    const ACCESS_GETTER_SETTER = 1;

    /**
     * Field is hidden
     */
    const VISIBILITY_NONE = 'none';
    /**
     * Field is visible in list
     */
    const VISIBILITY_LIST = 'list';
    /**
     * Field is visible in object view
     */
    const VISIBILITY_VIEW = 'view';
    /**
     * Field is visible in form view
     */
    const VISIBILITY_EDIT = 'edit';
    /**
     * Field is queryable
     */
    const VISIBILITY_QUERY = 'query';

    protected $property;

    protected $internalProperty;

    protected $type;

    protected $isArray = false;

    protected $isHash = false;

    protected $relatedModel;

    protected $relatedModelController;

    protected $relatedModelEmbed = false;

    protected $relatedModelActions = array('add', 'create', 'remove');

    protected $name;

    protected $description;

    protected $isIdentifier = false;

    protected $isStringRepr = false;

    protected $optional = false;

    protected $defaultValue;

    protected $accessMethod = 0;

    protected $setterName;

    protected $getterName;

    protected $formFieldType;

    protected $formFieldOptions;

    protected $visibility = array(
        FieldDefinition::VISIBILITY_LIST,
        FieldDefinition::VISIBILITY_VIEW,
        FieldDefinition::VISIBILITY_EDIT,
        FieldDefinition::VISIBILITY_QUERY
    );

    protected $constraints = array();

    protected $defaultFormType = 'text';

    protected $formTypeMapping = array(
        'string' => 'text',
        'int' => 'text',
        'double' => 'text',
        'float' => 'text',
        'bool' => 'checkbox',
        'boolean' => 'checkbox',
        'resource' => 'file'
    );

    protected $valueController;

    protected $valueControllerRemoteId;

    protected $valueControllerLocalId;

    protected $valueControllerEmbed;

    protected $i18n;

    protected $isInternal = false;

    public static function create()
    {
        return new FieldDefinition();
    }

    /**
     * The object property
     *
     * @param string $name
     */
    public function setProperty($name)
    {
        $this->property = $name;
        if ($this->internalProperty === null) {
            $this->internalProperty = $name;
        }
        return $this;
    }

    public function getProperty()
    {
        return $this->property;
    }

    /**
     * Sets the actual name of the object properties (defaults to the property name)
     *
     * @param string $name
     */
    public function setInternalProperty($name)
    {
        $this->internalProperty = $name;
        return $this;
    }

    public function getInternalProperty()
    {
        return $this->internalProperty;
    }

    /**
     * Sets the type: class name or php type
     *
     * If ended with [], it will be marked as an array
     * If "hash", it will be marked as an hash of strings
     *
     * @param string $type
     */
    public function setType($type)
    {
        if (strpos($type, '[]') !== false) {
            $this->isArray = true;
            $type = rtrim($type, '[]');
        } else if ($type == 'array') {
            $this->isArray = true;
            $type = 'string';
        } else if ($type == 'hash') {
            $this->isHash = true;
            $type = 'string';
        }

        $this->type = $type;

        if ($this->formFieldType === null) {
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

    /**
     * Returns the type as a formated string
     *
     * @return string
     */
    public function getFormatedType()
    {
        $suffix = '';
        if ($this->isHash) {
            $suffix = '{}';
        } else if ($this->isArray) {
            $suffix = '[]';
        }
        return $this->type . $suffix;
    }

    /**
     * Sets the related model
     *
     * When the type is object, a ModelDefinition can be used to defined the nature of this object.
     * This can be used to make child objects editable. Possible actions are view, edit, remove, create.
     *
     * @param ModelDefinition $model
     * @param string $getter Getter method to access the object
     * @param string $modelController Controller name used to manipulate this object
     * @param array $actions Which actions can be performed on this model
     * @param boolean $embed Whether to embed this model in object view
     */
    public function setRelatedModel(ModelDefinition $model, $getter = null, $modelController = null, $actions = null, $embed = false)
    {
        $this->relatedModel = $model;
        $this->relatedModelGetter = $getter;
        $this->relatedModelController = $modelController;
        $this->relatedModelEmbed = $embed;
        if ($actions !== null) {
            $this->relatedModelActions = $actions;
        }
        return $this;
    }

    public function hasRelatedModel()
    {
        return $this->relatedModel !== null;
    }

    public function getRelatedModel()
    {
        return $this->relatedModel;
    }

    public function getRelatedModelController()
    {
        return $this->relatedModelController;
    }

    public function isRelatedModelEmbeded()
    {
        return $this->relatedModelEmbed;
    }

    public function getRelatedModelActions()
    {
        return $this->relatedModelActions;
    }

    /**
     * Sets whether the data is an array (where items will be of the given type)
     *
     * @param boolean $isArray
     */
    public function setIsArray($isArray = true)
    {
        $this->isArray = $isArray;
        return $this;
    }

    public function isArray()
    {
        return $this->isArray;
    }

    /**
     * Sets whether the data is an hash. Keys are string.
     *
     * @param boolean $isHash
     * @param array $possibleKeys Array of strings limiting which keys are allowed
     */
    public function setIsHash($isHash = true, array $possibleKeys = array())
    {
        $this->isHash = $isHash;
        if (!empty($possibleKeys)) {
            $this->formFieldOptions['possible_keys'] = $possibleKeys;
        }
        return $this;
    }

    public function isHash()
    {
        return $this->isHash;
    }

    /**
     * Name of the field as it is viewed by the user in the dashboard
     *
     * @param string $name
     */
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

    /**
     * Sets whether this fields identifies the model
     *
     * @param boolean $isIdentifier
     */
    public function setIdentifier($isIdentifier = true)
    {
        $this->isIdentifier = $isIdentifier;
        return $this;
    }

    public function isIdentifier()
    {
        return $this->isIdentifier;
    }

    /**
     * Sets whether this string is used as the string representation of the model
     *
     * @param boolean $isStringRepr
     */
    public function setStringRepr($isStringRepr = true)
    {
        $this->isStringRepr = $isStringRepr;
        return $this;
    }

    public function isStringRepr()
    {
        return $this->isStringRepr;
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

    public function setGetterMethodName($getter)
    {
        $this->getterName = $getter;
        return $this;
    }

    public function getGetterMethodName()
    {
        if ($this->getterName === null) {
            return 'get' . ucfirst($this->internalProperty);
        }
        return $this->getterName;
    }

    public function setSetterMethodName($setter)
    {
        $this->setterName = $setter;
        return $this;
    }

    public function getSetterMethodName()
    {
        if ($this->setterName === null) {
            return 'set' . ucfirst($this->internalProperty);
        }
        return $this->setterName;
    }

    /**
     * Sets the type of html input used in html forms
     *
     * @param string $type HTML input type (any type of input or textarea or richtext)
     * @param array $options
     */
    public function setFormFieldType($type, $options = null)
    {
        $this->formFieldType = $type;
        $this->formFieldOptions = $options;
        return $this;
    }

    public function getFormFieldType()
    {
        return $this->formFieldType;
    }

    public function setFormFieldOptions($options, $merge = false)
    {
        if ($merge && $this->formFieldOptions !== null) {
            $this->formFieldOptions = array_merge($this->formFieldOptions, $options);
        } else {
            $this->formFieldOptions = $options;
        }
        return $this;
    }

    public function getFormFieldOptions()
    {
        return $this->formFieldOptions;
    }

    public function setVisibility($visibility)
    {
        $this->visibility = (array) $visibility;
        return $this;
    }

    public function getVisibility()
    {
        return $this->visibility;
    }

    public function isVisible($visibility)
    {
        return in_array($visibility, $this->visibility);
    }

    /**
     * Sets Symfony validator constraints
     *
     * @param array $constraints
     */
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
     * Sets the value controller
     *
     * When using object as type and a related model, this defines the controller which can be used
     * to manipulate the object.
     *
     * @param string $controllerName
     * @param string $remoteId
     * @param string $localId
     * @param boolean $embed
     */
    public function setValueController($controllerName, $remoteId = 'id', $localId = null, $embed = true)
    {
        $this->valueController = $controllerName;
        $this->valueControllerRemoteId = $remoteId;
        $this->valueControllerLocalId = $localId;
        $this->valueControllerEmbed = $embed;
        return $this;
    }

    public function hasValueController()
    {
        return $this->valueController !== null;
    }

    public function getValueController()
    {
        return $this->valueController;
    }

    public function getValueControllerRemoteId()
    {
        return $this->valueControllerRemoteId;
    }

    public function getValueControllerLocalId()
    {
        return $this->valueControllerLocalId;
    }

    public function isValueControllerEmbeded()
    {
        return $this->valueControllerEmbed;
    }

    /**
     * Sets whether this field is translatable and which locales it supports
     *
     * @param array $locales Strings of locale names (eg: en, fr)
     */
    public function setI18n(array $locales)
    {
        $this->i18n = $locales;
        return $this;
    }

    public function isTranslatable()
    {
        return !empty($this->i18n);
    }

    public function getI18n()
    {
        return $this->i18n;
    }

    public function isSerializable()
    {
        return $this->type != 'resource';
    }

    /**
     * Sets whether this is an internal field
     *
     * @param boolean $internal
     */
    public function setInternal($internal = true)
    {
        $this->isInternal = $internal;
        return $this;
    }

    public function isInternal()
    {
        return $this->isInternal;
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
            if (!property_exists($object, $this->internalProperty)) {
                $v = $this->defaultValue;
            } else {
                $v = $object->{$this->internalProperty};
            }
        } else {
            $v = call_user_func(array($object, $this->getGetterMethodName()));
        }

        if ($this->isTranslatable()) {
            $values = array();
            foreach ($this->i18n as $locale) {
                $values[$locale] = call_user_func(array($object, $this->getGetterMethodName()), array(), $locale, false);
            }
            return $values;
        }

        if ($this->relatedModel && !$this->isArray() && $v !== null) {
            $v = array('id' => $v, 'repr' => $v);
            if ($this->relatedModelGetter) {
                if ($related = call_user_func(array($object, $this->relatedModelGetter))) {
                    $v['repr'] = $this->relatedModel->getObjectRepr($related);
                    if ($this->relatedModelEmbed) {
                        $v['data'] = $this->relatedModel->convertObjectToArray($related);
                    }
                }
            }
        }

        return $v;
    }

    /**
     * Sets the field's valud on a object
     *
     * @param object $object
     * @param mixed $value
     */
    public function setValue($object, $value)
    {
        if ($this->isArray && $this->relatedModel !== null) {
            $m = $this->relatedModel;
            $value = array_map(function($v) use ($m) {
                return $m->instanciateObject($v);
            }, array_filter($value));
        } else if ($this->isArray && empty($value)) {
            $value = array();
        }

        if ($this->formFieldType == 'file' && substr($value, 0, 5) == 'data:') {
            $value = base64_decode(substr($value, strpos($value, ',') + 1));
        }

        if ($this->isAccessedUsingProperty()) {
            $object->{$this->internalProperty} = $value;
            return $this;
        }

        if (!$this->isTranslatable()) {
            call_user_func(array($object, $this->getSetterMethodName()), $value);
            return $this;
        }

        foreach ($value as $locale => $v) {
            call_user_func(array($object, $this->getSetterMethodName()), $v, $locale);
        }

        return $this;
    }
}
