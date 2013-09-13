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
     * The menu which will contain the actions of this controller
     */
    public $menu;
}
