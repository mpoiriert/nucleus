<?php

namespace Nucleus\IService\Dashboard;

/**
 * @Annotation
 */
class ListColumn
{
    public $name;
    public $property;

    public function asArray()
    {
        return array('columns' => array($this->name));
    }
}
