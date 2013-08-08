<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Dashboard;

use Nucleus\IService\Invoker\IInvokerService;
use Nucleus\IService\Security\IAccessControlService;
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

    public $accessControl;

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
        $this->accessControl = $serviceContainer->get('accessControl');
        $this->builder = $serviceContainer->get('dashboardDefinitionBuilder');
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
     * @Route(name="dashboard.schema", path="/nucleus/dashboard/_schema")
     */
    public function getSchema()
    {
        $schema = array();
        foreach ($this->controllers as $controller) {
            $schema[] = array(
                'name' => $controller->getName(),
                'title' => $controller->getTitle(),
                'url' => $this->routing->generate('dashboard.controllerSchema', 
                    array('controllerName' => $controller->getName()))
            );
        }

        return $this->formatResponse($schema);
    }

    /**
     * @Route(name="dashboard.controllerSchema", path="/nucleus/dashboard/{controllerName}/_schema")
     */
    public function getControllerSchema($controllerName)
    {
        if (($controller = $this->getController($controllerName)) === false) {
            throw new DashboardException("Controller '$controllerName' not found");
        }

        $self = $this;
        $schema = array_values(array_filter(array_map(function($action) use ($controller, $self) {
            if (!$self->accessControl->checkPermissions($action->getPermissions())) {
                return false;
            }
            return array_merge($self->formatAction($action), array(
                'default' => $action->isDefault(),
                'url' => $self->routing->generate('dashboard.actionSchema', 
                    array('controllerName' => $controller->getName(), 'actionName' => $action->getName()))
            ));
        }, $controller->getVisibleActions())));

        return $this->formatResponse($schema);
    }

    /**
     * @Route(name="dashboard.actionSchema", path="/nucleus/dashboard/{controllerName}/{actionName}/_schema")
     */
    public function getActionSchema($controllerName, $actionName)
    {
        list($controller, $action) = $this->getAction($controllerName, $actionName);

        $schema = array_merge(
            $this->formatAction($action),
            array('input' => $this->formatActionInput($controller, $action)),
            array('output' => $this->formatActionOutput($controller, $action))
        );

        return $this->formatResponse($schema);
    }

    /**
     * @Route(name="dashboard.modelActionSchema", path="/nucleus/dashboard/{controllerName}/{actionName}/{modelActionName}/_schema")
     */
    public function getModelActionSchema($controllerName, $actionName, $modelActionName)
    {
        list($controller, $action, $modelAction) = $this->getAction($controllerName, $actionName, $modelActionName);

        $schema = array_merge(
            $this->formatAction($modelAction),
            array('name' => $action->getName() . '/' . $modelAction->getName()),
            array('input' => array_merge(
                $this->formatActionInput($controller, $action),
                array('url' => $this->routing->generate('dashboard.invokeModel', array(
                    'controllerName' => $controller->getName(), 'actionName' => $action->getName(), 
                    'modelActionName' => $modelAction->getName()))
                )
            )),
            array('output' => $this->formatActionOutput($controller, $modelAction))
        );

        return $this->formatResponse($schema);
    }

    /**
     * @Route(name="dashboard.invoke", path="/nucleus/dashboard/{controllerName}/{actionName}")
     */
    public function invokeAction($controllerName, $actionName, Request $request, Response $response)
    {
        list($controller, $action) = $this->getAction($controllerName, $actionName);

        $service = $this->serviceContainer->get($controller->getServiceName());
        $model = $action->getInputModel();
        $data = $this->getInputData($request);

        try {
            if ($action->isModelOnlyArgument()) {
                $object = $this->instanciateModel($model, $data);
                $model->validate($object);
                $data = array($action->getModelArgumentName() => $object);
            } else if ($model) {
                $model->validate($data);
            }
        } catch (ValidationException $e) {
            return $this->formatErrorResponse((string) $e->getVioliations());
        }

        $result = $this->invoker->invoke(
            array($service, $action->getName()), $data, array($request, $response));

        return $this->formatInvokedResponse($action, $result);
    }

    /**
     * @Route(name="dashboard.invokeModel", path="/nucleus/dashboard/{controllerName}/{actionName}/{modelActionName}")
     */
    public function invokeModelAction($controllerName, $actionName, $modelActionName, Request $request, Response $response)
    {
        list($controller, $action, $modelAction) = $this->getAction($controllerName, $actionName, $modelActionName);

        $data = $this->getInputData($request);
        $model = $action->getReturnModel();
        $object = $this->instanciateModel($model, $data);

        try {
            $model->validate($object);
        } catch (ValidationException $e) {
            return $this->formatErrorResponse((string) $e->getVioliations());
        }

        $result = $this->invoker->invoke(
            array($object, $modelAction->getName()), array(), array($request, $response));

        return $this->formatInvokedResponse($modelAction, $result, $object);
    }

    protected function getAction($controllerName, $actionName, $modelActionName = null)
    {
        if (($controller = $this->getController($controllerName)) === false) {
            throw new DashboardException("Controller '$controllerName' not found");
        }

        if (($action = $controller->getAction($actionName)) === false) {
            throw new DashboardException("Action '$actionName' of '$controllerName' not found");
        }

        if (!$this->accessControl->checkPermissions($action->getPermissions())) {
            throw new DashboardException("Missing permissions to use action '$actionName' of '$controllerName'");
        }

        if ($modelActionName === null) {
            return array($controller, $action);
        }

        if (($modelAction = $action->getReturnModel()->getAction($modelActionName)) === false) {
            throw new DashboardException("Action '$modelActionName' from model of action '$actionName' of '$controllerName' not found");
        }

        if (!$this->accessControl->checkPermissions($modelAction->getPermissions())) {
            throw new DashboardException("Missing permissions to use action '$actionName/$modelActionName' of '$controllerName'");
        }

        return array($controller, $action, $modelAction);
    }

    protected function getInputData(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            return json_decode($request->request->get('data'), true);
        }
        return $request->query->all();
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
        $json['actions'] = array_merge(
            array_values(array_filter(array_map(function($modelAction) use ($controller, $action, $self) {
                if (!$self->accessControl->checkPermissions($modelAction->getPermissions())) {
                    return false;
                }
                return array_merge($self->formatAction($modelAction), array(
                    'name' => $action->getName() . '/' . $modelAction->getName(),
                    'url' => $self->routing->generate('dashboard.invokeModel', 
                        array('controllerName' => $controller->getName(), 'actionName' => $action->getName(), 
                            'modelActionName' => $modelAction->getName()))
                ));
            }, $action->getReturnModel()->getActions()))),

            array_values(array_filter(array_map(function($action) use ($controller, $self) {
                if (!$self->accessControl->checkPermissions($action->getPermissions())) {
                    return false;
                }
                return array_merge($self->formatAction($action), array(
                    'url' => $self->routing->generate('dashboard.invoke', 
                        array('controllerName' => $controller->getName(), 'actionName' => $action->getName()))
                ));
            }, $controller->getActionsForModel($action->getReturnModel()->getClassName()))))
        );

        if ($action->getReturnType() === ActionDefinition::RETURN_FORM) {
            $json['fields'] = $this->formatFields($action->getReturnModel()->getEditableFields());
        } else {
            $json['fields'] = $this->formatFields($action->getReturnModel()->getListableFields());
        }

        if ($action->isPiped()) {
            $json['pipe'] = $action->getPipe();
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
                'type' => $f->getType(),
                'is_array' => $f->isArray(),
                'field_type' => $f->getFormFieldType(),
                'formated_type' => $f->getFormatedType(),
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

    protected function formatResponse($data)
    {
        return array('success' => true, 'data' => $data);
    }

    protected function formatErrorResponse($message)
    {
        return array('success' => false, 'message' => $message);
    }

    protected function formatInvokedResponse($action, $result, $obj = null)
    {
        $model = $action->getReturnModel();
        if ($action->getReturnType() === ActionDefinition::RETURN_LIST) {
            $data = array();
            if ($result !== null) {
                foreach ($result as $item) {
                    $data[] = $this->convertObjectToJson($item, $model);
                }
            }
        } else if ($action->getReturnType() === ActionDefinition::RETURN_NONE) {
            $data = null;
        } else if ($result === null) {
            $data = null;
        } else {
            $data = $this->convertObjectToJson($result, $model);
        }
        return $this->formatResponse($data);
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
