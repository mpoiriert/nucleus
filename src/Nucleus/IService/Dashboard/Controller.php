<?php

namespace Nucleus\IService\Dashboard;

/**
 * @Annotation
 */
class Controller
{
    /**
     * The internal name of the controller. 
     * Used to generate URLs.
     */
    public $name;

    /**
     * The public title of the controller
     * Used in the menu bar.
     */
    public $title;
}
