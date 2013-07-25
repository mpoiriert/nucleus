<?php

namespace Nucleus\IService\Dashboard;

/**
 * @Annotation
 */
class Action
{
    public $title;
    public $icon;
    public $in;
    public $out;
    public $model;
    public $default = false;
    public $visible = true;
    public $pipe;
}
