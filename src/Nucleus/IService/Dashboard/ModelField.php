<?php

namespace Nucleus\IService\Dashboard;

/**
 * @Annotation
 */
class ModelField
{
    /**
     * Public name of the field
     */
    public $name;

    /**
     * Description
     */
    public $description;

    /**
     * Name of the property
     * Will be used to computer the name of the getter/setter
     */
    public $property;

    /**
     * Type
     */
    public $type;

    /**
     * If this property is the model's identifier
     */
    public $identifier = false;

    /**
     * Getter method
     */
    public $getter;

    /**
     * Setter method
     */
    public $setter;

    /**
     * The type of HTML input
     */
    public $formField;

    /**
     * Visibility
     */
    public $visibility;

    /**
     * Whether this property is required
     */
    public $required = false;
}
