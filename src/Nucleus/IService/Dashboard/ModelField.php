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
     * The type of HTML input
     */
    public $formField;

    /**
     * Whether this property is listable
     */
    public $listable = true;

    /**
     * Whether this property is editable
     */
    public $editable = true;

    /**
     * The name of an action which will be triggered when
     * this property is clicked
     */
    public $link;

    /**
     * Whether this property is required
     */
    public $required = false;
}
