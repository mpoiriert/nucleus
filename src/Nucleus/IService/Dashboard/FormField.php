<?php

namespace Nucleus\IService\Dashboard;

/**
 * @Annotation
 */
class FormField
{
    public $name;
    public $label;
    public $type = 'text';

    public function asArray()
    {
        return array('fields' => array(
            array('name' => $this->name, 'label' => $this->label, 'type' => $this->type)
        ));
    }
}
