<?php

namespace Nucleus\Dashboard;

use Nucleus\Dashboard\ActionBehaviors\AbstractActionBehavior;

/**
 * Definition of an action in the dashboard
 *
 * Actions are defined by a few properties and have
 * one input and one output methods. They are tied
 * to a model which defines the data structure of the
 * input/output.
 *
 * On top of the output method, actions can define how
 * this output should be treated, called a "flow".
 *
 * Behaviors can be attached to the action which can
 * impact how the input or output is displayed.
 */
class ActionDefinition
{
    /**
     * No input, the action is not called
     */
    const INPUT_NONE = 'none';
    /**
     * The action is called without parameters (default)
     */
    const INPUT_CALL = 'call';
    /**
     * The action is called with parameters defined by the input model
     */
    const INPUT_FORM = 'form';
    /**
     * The action is called with arbitrary parameters. The action's callback
     * will receive a single $data parameters which is an array of data
     */
    const INPUT_DYNAMIC = 'dynamic';

    /**
     * No output (default)
     */
    const RETURN_NONE = 'none';
    /**
     * The action returns a list of data (array or iterator)
     */
    const RETURN_LIST = 'list';
    /**
     * The action returns a single object (object or array)
     */
    const RETURN_OBJECT= 'object';
    /**
     * The object returns a single object but the output will be a form (to be used with a flow)
     */
    const RETURN_FORM = 'form';
    /**
     * The actions returns a redirect to another action
     */
    const RETURN_REDIRECT = 'redirect';
    /**
     * The action returns a file
     */
    const RETURN_FILE = 'file';
    /**
     * The action returns an ActionDefinition object which output method will be used and associated data
     */
    const RETURN_DYNAMIC = 'dynamic';
    /**
     * The action returns an ActionDefinition object which will be used to construct a new action
     */
    const RETURN_BUILDER = 'builder';
    /**
     * The action returns an html string
     */
    const RETURN_HTML = 'html';

    /**
     * No flow (default)
     */
    const FLOW_NONE = 'none';
    /**
     * Call to this action is delegated to another action but this action's output definition will be used
     */
    const FLOW_DELEGATE = 'delegate';
    /**
     * When using FORM return, submitting the returned form will trigger the piped action
     */
    const FLOW_PIPE = 'pipe';
    /**
     * Redirects
     */
    const FLOW_REDIRECT = 'redirect';
    /**
     * Redirects with the identifier key of the returned object (in combination with OBJECT return)
     */
    const FLOW_REDIRECT_WITH_ID = 'redirect_with_id';
    /**
     * Redirects with the data of the returned object (in combination with OBJECT return)
     */
    const FLOW_REDIRECT_WITH_DATA = 'redirect_with_data';

    protected $name;

    protected $title;

    protected $icon;

    protected $description;

    protected $default = false;

    protected $menu = true;

    protected $inputType = ActionDefinition::INPUT_CALL;

    protected $inputModel;

    protected $modelOnlyArgument = false;

    protected $loadModel = false;

    protected $returnType = ActionDefinition::RETURN_NONE;

    protected $returnModel;

    protected $flow = ActionDefinition::FLOW_NONE;

    protected $nextAction;

    protected $appliedToModel;

    protected $permissions = array();

    protected $behaviors = array();

    public static function create()
    {
        return new ActionDefinition();
    }

    /**
     * Sets the internal name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
        if ($this->title === null) {
            $this->title = $name;
        }
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name displayed to the user
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function setDescription($desc)
    {
        $this->description = $desc;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Defines if this is the default action of the controller
     *
     * @param bool $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
        return $this;
    }

    public function isDefault()
    {
        return $this->default;
    }

    /**
     * Sets the menu path under which this action should be available.
     * Use false to hide this action.
     *
     * @param boolean $menu
     */
    public function setMenu($menu = true)
    {
        $this->menu = $menu;
        return $this;
    }

    public function getMenu()
    {
        if ($this->menu === true) {
            return $this->title;
        }
        return trim(rtrim($this->menu, '/') . '/' . $this->title, '/');
    }

    public function providesMenu()
    {
        return $this->menu !== null && !is_bool($this->menu);
    }

    public function isVisible()
    {
        return $this->menu !== false;
    }

    public function setInputType($type)
    {
        $this->inputType = $type;
        return $this;
    }

    public function getInputType()
    {
        return $this->inputType;
    }

    public function setInputModel(ModelDefinition $model)
    {
        $this->inputModel = $model;
        return $this;
    }

    public function getInputModel()
    {
        return $this->inputModel;
    }

    /**
     * Sets whether this action needs a single object as first argument which is defined by the input model
     *
     * @param string $fieldName Name of the argument
     */
    public function setModelOnlyArgument($fieldName)
    {
        $this->modelOnlyArgument = $fieldName;
        return $this;
    }

    public function isModelOnlyArgument()
    {
        return $this->modelOnlyArgument !== false;
    }

    public function getModelArgumentName()
    {
        return $this->modelOnlyArgument;
    }

    /**
     * When using setModelOnlyArgument(true), defines whether the model should be loaded or created
     *
     * @param boolean $load
     */
    public function setLoadModel($load = true)
    {
        $this->loadModel = $load;
        return $this;
    }

    public function isModelLoaded()
    {
        return $this->loadModel;
    }

    public function setReturnType($type)
    {
        $this->returnType = $type;
        return $this;
    }

    public function getReturnType()
    {
        return $this->returnType;
    }

    public function setReturnModel(ModelDefinition $model)
    {
        $this->returnModel = $model;
        return $this;
    }

    public function getReturnModel()
    {
        return $this->returnModel;
    }

    public function setFlow($flow, $nextActionName = null)
    {
        $this->flow = $flow;
        if ($nextActionName !== null) {
            $this->setNextAction($nextActionName);
        }
        if ($flow == self::FLOW_REDIRECT && substr($nextActionName, 0, 1) == '$') {
            $this->returnType = self::RETURN_REDIRECT;
        } else if ($flow !== self::FLOW_NONE) {
            $this->returnType = self::RETURN_FORM;
        }
        return $this;
    }

    public function getFlow()
    {
        return $this->flow;
    }

    public function isFlowing()
    {
        return $this->flow !== self::FLOW_NONE;
    }

    /**
     * Sets the name of the next action when using a flow
     *
     * @param string $actionName
     */
    public function setNextAction($actionName)
    {
        if ($this->flow === self::FLOW_NONE) {
            throw new DefinitionBuilderException("Flow must be set to something else than return to set a next action");
        }
        $this->nextAction = $actionName;
        return $this;
    }

    public function getNextAction()
    {
        return $this->nextAction;
    }

    /**
     * Sets whether this action only applies to a specific model
     *
     * This can be used to create model actions using actions defined as controller actions.
     *
     * @param string $className
     */
    public function applyToModel($className)
    {
        if (!$className) {
            $this->appliedToModel = $className === false ? false : null;
        } else {
            $this->appliedToModel = trim($className, '\\');
        }
        return $this;
    }

    public function isAppliedToModel()
    {
        return !empty($this->appliedToModel);
    }

    public function getAppliedToModel()
    {
        return $this->appliedToModel;
    }

    public function setPermissions(array $perms)
    {
        $this->permissions = $perms;
        return $this;
    }

    public function getPermissions()
    {
        return $this->permissions;
    }

    public function addBehavior(AbstractActionBehavior $behavior)
    {
        $this->behaviors[] = $behavior;
        $behavior->setAction($this);
        return $this;
    }

    public function getBehavior($name)
    {
        foreach ($this->behaviors as $behavior) {
            if ($behavior->getName() === $name) {
                return $behavior;
            }
        }
        return null;
    }

    public function getBehaviors()
    {
        return $this->behaviors;
    }

    public function applyBehaviors($eventName, $args)
    {
        foreach ($this->behaviors as $behavior) {
            $behavior->trigger($eventName, $args);
        }
        return $this;
    }
}
