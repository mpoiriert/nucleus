<?php

namespace Nucleus\IService\Dashboard;

/**
 * @Annotation
 */
class Action
{
    public $title;
    public $icon;
    public $type = 'call';
    public $default = false;
    public $global = true;

    public function asArray()
    {
        return array(
            'type' => $this->type,
            'method' => $this->type == 'form' ? 'post' : 'get'
        );
    }
}
