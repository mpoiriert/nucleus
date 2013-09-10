<?php

namespace Nucleus\IService\Dashboard;

/**
 * @Annotation
 */
class Validate
{
    /**
     * Which property to validate
     */
    public $property;

    /**
     * Name of the constraint
     */
    public $constraint;

    /**
     * JSON string of options
     */
    public $options = '{}';
}
