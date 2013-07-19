<?php

namespace Nucleus\IService\Dashboard;

/**
 * @Annotation
 */
class ListAction
{
    public $name;
    public $icon;
    public $title;

    public function asArray()
    {
        return array('actions' => array(
            array('name' => $this->name, 'icon' => $this->icon, 'title' => $this->title)
        ));
    }
}
