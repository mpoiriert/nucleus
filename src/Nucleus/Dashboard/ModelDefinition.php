<?php

namespace Nucleus\Dashboard;

use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\ConstraintViolationList;
use ReflectionClass;

/**
 * A model is a data structure use by the dashboard to carry data around
 *
 * A model is named and composed of fields and actions. Model actions are
 * methods of the model object.
 */
class ModelDefinition
{
    /**
     * Validates data with a Symfony validator
     */
    const VALIDATE_WITH_VALIDATOR = 0;
    /**
     * Validates data using a callback
     */
    const VALIDATE_WITH_CALLBACK = 1;
    /**
     * Validates data using a method named "validate" of the model
     */
    const VALIDATE_WITH_METHOD = 2;

    protected $className;

    protected $name;

    protected $fields = array();

    protected $actions = array();

    protected $loader;

    protected $validationMethod = ModelDefinition::VALIDATE_WITH_VALIDATOR;

    public static function create()
    {
        return new ModelDefinition();
    }

    public function setClassName($className)
    {
        $this->className = trim($className, '\\');
        if ($this->name === null) {
            $parts = explode('\\', $this->className);
            $this->name = array_pop($parts);
        }
        return $this;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function isAutoGenerated()
    {
        return $this->className === null;
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

    public function setFields(array $fields)
    {
        $this->fields = array();
        array_map(array($this, 'addField'), $fields);
        return $this;
    }

    public function addField(FieldDefinition $field)
    {
        $this->fields[$field->getName()] = $field;
        return $this;
    }

    public function getField($name)
    {
        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }
        return null;
    }

    public function getFieldByProperty($name)
    {
        foreach ($this->fields as $field) {
            if ($field->getProperty() === $name) {
                return $field;
            }
        }
        return null;
    }

    public function getIdentifierFields()
    {
        $idfields = array();
        foreach ($this->fields as $field) {
            if ($field->isIdentifier()) {
                $idfields[] = $field;
            }
        }
        return $idfields;
    }

    public function getStringReprField()
    {
        foreach ($this->fields as $field) {
            if ($field->isStringRepr()) {
                return $field;
            }
        }
        if ($ids = $this->getIdentifierFields()) {
            return $ids[0];
        }
        return reset($this->fields);
    }

    public function getFields()
    {
        return array_values($this->fields);
    }

    public function getVisibleFields($visibility)
    {
        return array_filter($this->fields, function($f) use ($visibility) { 
            return $f->isVisible($visibility); });
    }

    public function getPublicFields()
    {
        return array_filter($this->fields, function($f) { return !$f->isInternal(); });
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
        if ($action->getAppliedToModel() !== false) {
            $action->applyToModel($this->className);
        }
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
    
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * The callback used to load a model.
     *
     * The callback will be called with the parameters given to the action
     *
     * @param callback $callback
     */
    public function setLoader($callback = null)
    {
        $this->loader = $callback;
        return $this;
    }

    public function getLoader()
    {
        return $this->loader;
    }

    public function hasLoader()
    {
        return $this->loader !== null;
    }

    public function setValidationMethod($method)
    {
        $this->validationMethod = $method;
        return $this;
    }

    public function getValidationMethod()
    {
        return $this->validationMethod;
    }

    /**
     * Sets the Symfony validator object or the validator callback depending on the validation method
     *
     * @param mixed $validator
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
        return $this;
    }

    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Loads a model using the $data according to the ModelDefinition (eg: using the model loader)
     *
     * @param array $data
     * @return object
     */
    public function loadObject($data)
    {
        if ($this->loader === null) {
            return $this->instanciateObject($data);
        }

        $id = array();
        foreach ($this->getIdentifierFields() as $idf) {
            $id[] = $data[$idf->getProperty()];
        }

        if (count($id) === 1) {
            $args = array($id[0]);
        } else {
            $args = empty($id) ? $data : array($id);
        }

        if (!($obj = call_user_func_array($this->loader, $args))) {
            return null;
        }

        $this->populateObject($obj, $data);
        return $obj;
    }

    /**
     * Creates a new object according to the ModelDefinition
     *
     * @param array $data
     * @return object
     */
    public function instanciateObject($data = array())
    {
        if ($this->className !== null) {
            $class = new ReflectionClass($this->className);
            $obj = $class->newInstance();
        } else {
            $obj = new \stdClass();
        }
        $this->populateObject($obj, $data);
        return $obj;
    }

    /**
     * Populates an object with the specified data according to the given ModelDefinition
     *
     * @param object $obj
     * @param array $data
     * @return object
     */
    public function populateObject($obj, $data)
    {
        foreach ($this->getFields() as $f) {
            if (!$f->isInternal() && !$f->isVisible(FieldDefinition::VISIBILITY_EDIT)) {
                continue;
            }
            $p = $f->getProperty();
            if (!array_key_exists($p, $data)) {
                continue;
            }
            $f->setValue($obj, $data[$p]);
        }
        return $obj;
    }

    /**
     * Converts an object to an array representation according to the ModelDefinition
     *
     * @param object $obj
     * @return array
     */
    public function convertObjectToArray($obj, $ignoreSerializable = false)
    {
        $array = array();
        $class = new ReflectionClass($obj);
        foreach ($this->getFields() as $f) {
            if (!$f->isInternal() && ($ignoreSerializable || $f->isSerializable()) && (!$f->hasValueController() || !$f->isArray())) {
                $array[$f->getProperty()] = $f->getValue($obj);
            }
        }
        return $array;
    }

    /**
     * Returns the string representation of a model
     *
     * @return string
     */
    public function getObjectRepr($obj)
    {
        $f = $this->getStringReprField();
        return $f->getValue($obj);
    }

    /**
     * Validates an object using fields validators
     *
     * @param object $object
     */
    public function validateObject($object)
    {
        if ($this->validationMethod === self::VALIDATE_WITH_METHOD) {
            if (!method_exists($object, 'validate')) {
                return true;
            }
            if (!call_user_func(array($object, 'validate'))) {
                $violations = implode("\n", $object->getValidationFailures());
                throw new ValidationException($violations);
            }
            return true;
        }

        if ($this->validationMethod === self::VALIDATE_WITH_CALLBACK) {
            if ($this->validator === null) {
                throw new DefinitionBuilderException("Missing validation callback");
            }
            return call_user_func($this->validator, array($object, $this));
        }

        if ($this->validator === null) {
            return true;
        }

        if ($this->className !== null) {
            $violiations = $this->validator->validate($object);
        } else {
            $violiations = new ConstraintViolationList();
            foreach ($this->fields as $field) {
                $value = $field->getValue($object);
                $violiations->addAll($this->validator->validateValue($value, $field->getConstraints()));
            }
        }

        if (count($violiations)) {
            throw new ValidationException($violiations);
        }
        return true;
    }
}
