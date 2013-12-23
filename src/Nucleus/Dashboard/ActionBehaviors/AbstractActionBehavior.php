<?php

namespace Nucleus\Dashboard\ActionBehaviors;

/**
 * Base class for action behaviors
 *
 * Behaviors should subclass this class, create the getName() method, provide an array of params.
 * <code>
 * class MyBehavor extends AbstractActionBehavior {
 *   protected $params = array('myparam1' => 'value');
 *   public function getName() { return 'MyBehavior'; }
 * }
 * </code>
 * Optionnaly, behavior can have the following methods:
 *  - invoke(): to be directly invokable
 *  - beforeInvoke(): will be called before an action is invoked
 *  - afterInvoke(): will be called after an action has been invoked
 *  - beforeModelInvoke(): will be called before a model action is invoked
 *  - afterModelInvoke(): will be called after a model action has been invoked
 *  - formatInvokedResponse(): will be before the response is sent back
 */
abstract class AbstractActionBehavior
{
    /**
     * @var array
     */
    protected $params = array();

    protected $action;

    public function setAction($action)
    {
        $this->action = $action;
    }

    public function getAction()
    {
        return $this->action;
    }

    /**
     * Returns the name of the behavior
     *
     * @return string
     */
    abstract public function getName();

    public function __construct(array $params = array())
    {
        $this->setParams($params);
    }

    public function setParams(array $params)
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
        return $this;
    }

    public function getParam($name, $default = null)
    {
        if (array_key_exists($name, $this->params)) {
            return $this->params[$name];
        }
        return $default;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function isInvokable()
    {
        return method_exists($this, 'invoke');
    }

    public function trigger($eventName, $args)
    {
        if (method_exists($this, $eventName)) {
            return call_user_func_array(array($this, $eventName), $args);
        }
    }
}