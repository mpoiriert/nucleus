<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Dashboard;

use Nucleus\IService\Invoker\IInvokerService;
use Nucleus\Routing\Router;
use Nucleus\IService\DependencyInjection\IServiceContainer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ReflectionClass;

/**
 * Description of Dashboard
 *
 * @author Martin
 */
class Dashboard
{
    /**
     * @var \Nucleus\Routing\Router 
     */
    public $routing;

    private $serviceContainer;

    private $invoker;

    private $controllers = array();

    private $builder;

    /**
     * @param \Nucleus\Routing\Router $routing
     * 
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function initialize(IServiceContainer $serviceContainer, IInvokerService $invoker, Router $routing)
    {
        $this->serviceContainer = $serviceContainer;
        $this->invoker = $invoker;
        $this->routing = $routing;
        $this->builder = new DefinitionBuilder();
    }

    public function addController(ControllerDefinition $controller)
    {
        $this->controllers[$controller->getName()] = $controller;
    }

    public function addServiceAsController($serviceName)
    {
        $service = $this->serviceContainer->get($serviceName);
        $controller = $this->builder->buildController($service)->setServiceName($serviceName);
        $this->addController($controller);
    }

    public function getController($name)
    {
        return $this->controllers[$name];
    }

    public function getControllers()
    {
        return $this->controllers;
    }

    /**
     * @Route(name="dashboard.controllerUrls", path="/nucleus/dashboard/_schema")
     */
    public function getControllerUrls()
    {
        $controllers = array();
        foreach ($this->controllers as $controller) {
            $controllers[] = array(
                'name' => $controller->getName(),
                'title' => $controller->getTitle(),
                'url' => $this->routing->generate('dashboard.controllerSchema', 
                    array('controllerName' => $controller->getName()))
            );
        }
        return $controllers;
    }

    /**
     * @Route(name="dashboard.controllerSchema", path="/nucleus/dashboard/{controllerName}/_schema")
     */
    public function getControllerActions($controllerName)
    {
        if (($controller = $this->getController($controllerName)) === false) {
            throw new DashboardException("Controller '$controllerName' not found");
        }

        $self = $this;
        return array_map(function($action) use ($controller, $self) {
            return array_merge($self->formatAction($action), array(
                'default' => $action->isDefault(),
                'url' => $self->routing->generate('dashboard.actionSchema', 
                    array('controllerName' => $controller->getName(), 'actionName' => $action->getName()))
            ));
        }, $controller->getVisibleActions());
    }

    /**
     * @Route(name="dashboard.actionSchema", path="/nucleus/dashboard/{controllerName}/{actionName}/_schema")
     */
    public function getAction($controllerName, $actionName)
    {
        if (($controller = $this->getController($controllerName)) === false) {
            throw new DashboardException("Controller '$controllerName' not found");
        }

        if (($action = $controller->getAction($actionName)) === false) {
            throw new DashboardException("Action '$actionName' of '$controllerName' not found");
        }

        return array_merge(
            $this->formatAction($action),
            array('input' => $this->formatActionInput($controller, $action)),
            array('output' => $this->formatActionOutput($controller, $action))
        );
    }

    /**
     * @Route(name="dashboard.invoke", path="/nucleus/dashboard/{controllerName}/{actionName}")
     */
    public function invokeAction($controllerName, $actionName, Request $request, Response $response)
    {
        if (($controller = $this->getController($controllerName)) === false) {
            throw new DashboardException("Controller '$controllerName' not found");
        }

        if (($action = $controller->getAction($actionName)) === false) {
            throw new DashboardException("Action '$actionName' of '$controllerName' not found");
        }

        $service = $this->serviceContainer->get($controller->getServiceName());
        $params = array_merge($request->query->all(), $request->request->all());

        if ($action->isModelOnlyArgument()) {
            $model = $this->instanciateModel($action->getInputModel(), $params);
            $params = array($action->getModelArgumentName() => $model);
        }

        $result = $this->invoker->invoke(
            array($service, $action->getName()), $params, array($request, $response));

        return $this->formatResponse($action, $result);
    }

    /**
     * @Route(name="dashboard.invokeModel", path="/nucleus/dashboard/{controllerName}/{actionName}/{modelActionName}")
     */
    public function invokeModelAction($controllerName, $actionName, $modelActionName, Request $request, Response $response)
    {
        if (($controller = $this->getController($controllerName)) === false) {
            throw new DashboardException("Controller '$controllerName' not found");
        }

        if (($action = $controller->getAction($actionName)) === false) {
            throw new DashboardException("Action '$actionName' of '$controllerName' not found");
        }

        if (($modelAction = $action->getReturnModel()->getAction($modelActionName)) === false) {
            throw new DashboardException("Action '$modelActionName' from model of action '$actionName' of '$controllerName' not found");
        }

        $params = array_merge($request->query->all(), $request->request->all());
        $model = $this->instanciateModel($action->getInputModel(), $params);

        $result = $this->invoker->invoke(
            array($model, $modelAction->getName()), $params, array($request, $response));

        return $this->formatResponse($action, $result, $model);
    }

    public function formatAction(ActionDefinition $action)
    {
        return array(
            'name' => $action->getName(),
            'title' => $action->getTitle(),
            'icon' => $action->getIcon(),
            'description' => $action->getDescription()
        );
    }

    protected function formatActionInput(ControllerDefinition $controller, ActionDefinition $action)
    {
        $json = array(
            'type' => $action->getInputType(),
            'url' => $this->routing->generate('dashboard.invoke', 
                array('controllerName' => $controller->getName(), 'actionName' => $action->getName()))
        );

        if ($action->getInputType() !== ActionDefinition::INPUT_CALL) {
            $json['fields'] = $this->formatFields($action->getInputModel()->getEditableFields());
        }

        return $json;
    }

    protected function formatActionOutput(ControllerDefinition $controller, ActionDefinition $action)
    {
        if ($action->getReturnType() === ActionDefinition::RETURN_NONE) {
            return array('type' => $action->getReturnType());
        }

        $json = array('type' => $action->getReturnType());

        $self = $this;
        $json['actions'] = array_map(function($modelAction) use ($controller, $action, $self) {
            return array_merge($self->formatAction($modelAction), array(
                'url' => $self->routing->generate('dashboard.invokeModel', 
                    array('controllerName' => $controller->getName(), 'actionName' => $action->getName(), 
                        'modelActionName' => $modelAction->getName()))
            ));
        }, $action->getReturnModel()->getActions());

        if ($action->getReturnType() === ActionDefinition::RETURN_FORM) {
            $json['fields'] = $this->formatFields($action->getReturnModel()->getEditableFields());
        } else {
            $json['fields'] = $this->formatFields($action->getReturnModel()->getListableFields());
        }

        if ($action->isPiped()) {
            $json['url'] = $this->routing->generate('dashboard.actionSchema',
                array('controllerName' => $controller->getName(), 'actionName' => $action->getPipe()));
        }

        return $json;
    }

    protected function formatFields($fields)
    {
        $self = $this;
        return array_values(array_map(function($f) use ($self) {
            if ($link = $f->getLink()) {
                list($controller, $action) = explode('::', $link, 2);
                $link = array(
                    'controller' => $controller,
                    'action' => $action,
                    'url' => $self->routing->generate('dashboard.actionSchema',
                        array('controllerName' => $controller, 'actionName' => $action))
                );
            }

            return array(
                'type' => $f->getFormFieldType(),
                'name' => $f->getProperty(),
                'title' => $f->getName(),
                'description' => $f->getDescription(),
                'optional' => $f->isOptional(),
                'defaultValue' => $f->getDefaultValue(),
                'identifier' => $f->isIdentifier(),
                'link' => $link
            );
        }, $fields));
    }

    protected function formatResponse($action, $result, $obj = null)
    {
        $model = $action->getReturnModel();
        if ($action->getReturnType() === ActionDefinition::RETURN_LIST) {
            $list = array();
            foreach ($result as $item) {
                $list[] = $this->convertObjectToJson($item, $model);
            }
            return $list;
        }
        return $this->convertObjectToJson($result, $model);
    }

    protected function convertObjectToJson($obj, $model)
    {
        $json = array();
        $class = new ReflectionClass($obj);
        foreach ($model->getListableFields() as $f) {
            $p = $f->getProperty();
            $getter = 'get' . ucfirst($p);
            if ($class->hasProperty($p) && $class->getProperty($p)->isPublic()) {
                $json[$p] = $class->getProperty($p)->getValue($obj);
            } else if ($class->hasMethod($getter)) {
                $json[$p] = $class->getMethod($getter)->invoke($obj);
            }
        }
        return $json;
    }

    protected function instanciateModel(ModelDefinition $model, $data = array())
    {
        $className = $model->getClassName();
        $class = new ReflectionClass($className);
        $obj = $class->newInstance();
        foreach ($model->getEditableFields() as $f) {
            $p = $f->getProperty();
            $setter = 'set' . ucfirst($p);
            if (!isset($data[$p])) {
                continue;
            }
            if ($class->hasProperty($p)) {
                $class->getProperty($p)->setValue($obj, $data[$p]);
            } else if ($class->hasMethod($setter)) {
                $class->getMethod($setter)->invoke($obj, $data[$p]);
            }
        }
        return $obj;
    }
}
