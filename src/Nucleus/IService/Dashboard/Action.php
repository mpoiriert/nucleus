<?php

namespace Nucleus\IService\Dashboard;

/**
 * @Annotation
 */
class Action
{
    /**
     * The public title of the action.
     * Uses the method name if not defined
     */
    public $title;

    /**
     * A FontAwesome icon name for the menu
     */
    public $icon;

    /**
     * The type of input: call (default) or form
     */
    public $in;

    /**
     * The type of output: none (default), list, object or form
     */
    public $out;

    /**
     * The classname of the model which is returned
     */
    public $model;

    /**
     * Whether this is the default action of the controller
     */
    public $default = false;

    /**
     * An action name to pipe this action to
     */
    public $pipe;

    /**
     * Whether this action applies to a model only
     */
    public $on_model = false;

    /**
     * Whether to load the input model instead of creating a new one
     */
    public $load_model = false;

    /**
     * Menu name
     */
    public $menu;
}
