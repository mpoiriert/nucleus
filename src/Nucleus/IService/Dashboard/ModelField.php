<?php

namespace Nucleus\IService\Dashboard;

/**
 * @Annotation
 */
class ModelField
{
    public $name;
    public $description;
    public $property;
    public $type;
    public $identifier = false;
    public $formField = 'text';
    public $listable = true;
    public $editable = true;
    public $link;
    public $required = false;
}
