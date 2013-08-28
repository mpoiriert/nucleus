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
use Iterator;
use LimitIterator;
use Countable;
use ArrayIterator;

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
    
    private $configuration;

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
    
    /**
     * @param array $configuration
     * @\Nucleus\IService\DependencyInjection\Inject(configuration="$")
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
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
     * Loads the dashboard interface
     * 
     * @\Nucleus\IService\Routing\Route(name="dasboard", path="/nucleus/dashboard")
     * @\Nucleus\IService\FrontController\ViewDefinition(template="nucleus/dashboard/index.twig")
     */
    public function home()
    {
        return array('configuration' => $this->configuration);
    }

    /**
     * Returns the schema for all actions of all controllers
     * 
     * @\Nucleus\IService\Routing\Route(name="dashboard.schema", path="/nucleus/dashboard/_schema")
     */
    public function getSchema()
    {
        $schema = array();
        foreach ($this->controllers as $controller) {
            $schema = array_merge($schema, $this->getControllerActionsSchema($controller));
        }

        return $this->formatResponse($schema);
    }

    /**
     * Returns the schema for the actions of a controller
     * 
     * @param ControllerDefinition $controller
     * @return array
     */
    public function getControllerActionsSchema(ControllerDefinition $controller)
    {
        $self = $this;
        $actions = array_values(array_filter(array_map(function($action) use ($controller, $self) {
            if (!$self->accessControl->checkPermissions($action->getPermissions())) {
                return false;
            }
            return array_merge($self->formatAction($action), array(
                'controller' => $controller->getName(),
                'menu' => $action->getMenu(),
                'default' => $action->isDefault(),
                'url' => $self->routing->generate('dashboard.actionSchema', 
                    array('controllerName' => $controller->getName(), 'actionName' => $action->getName()))
            ));
        }, $controller->getActionsAsMenu())));

        return $actions;
    }

    /**
     * Returns the schema of an action
     * 
     * @\Nucleus\IService\Routing\Route(name="dashboard.actionSchema", path="/nucleus/dashboard/{controllerName}/{actionName}/_schema")
     */
    public function getActionSchema($controllerName, $actionName)
    {
        list($controller, $action) = $this->getAction($controllerName, $actionName);

        $schema = array_merge(
            $this->getLimitedActionSchema($action),
            array('input' => $this->getActionInputSchema($controller, $action)),
            array('output' => $this->getActionOutputSchema($controller, $action))
        );

        return $this->formatResponse($schema);
    }

    /**
     * Returns the schema of a model action
     * 
     * @\Nucleus\IService\Routing\Route(name="dashboard.modelActionSchema", path="/nucleus/dashboard/{controllerName}/{actionName}/{modelActionName}/_schema")
     */
    public function getModelActionSchema($controllerName, $actionName, $modelActionName)
    {
        list($controller, $action, $modelAction) = $this->getAction($controllerName, $actionName, $modelActionName);

        $schema = array_merge(
            $this->getLimitedActionSchema($modelAction),
            array('name' => $action->getName() . '/' . $modelAction->getName()),
            array('input' => array_merge(
                $this->getActionInputSchema($controller, $action),
                array('url' => $this->routing->generate('dashboard.invokeModel', array(
                    'controllerName' => $controller->getName(), 'actionName' => $action->getName(), 
                    'modelActionName' => $modelAction->getName()))
                )
            )),
            array('output' => $this->getActionOutputSchema($controller, $modelAction))
        );

        return $this->formatResponse($schema);
    }

    /**
     * Returns the limited schema for an action
     * 
     * @param ActionDefinition $action
     * @return array
     */
    public function getLimitedActionSchema(ActionDefinition $action)
    {
        return array(
            'name' => $action->getName(),
            'title' => $action->getTitle(),
            'icon' => $action->getIcon(),
            'description' => $action->getDescription()
        );
    }

    /**
     * Returns the schema of the input part of an action
     * 
     * @param ControllerDefinition $controller
     * @param ActionDefinition $action
     * @return array
     */
    protected function getActionInputSchema(ControllerDefinition $controller, ActionDefinition $action)
    {
        $json = array(
            'type' => $action->getInputType(),
            'url' => $this->routing->generate('dashboard.invoke', 
                array('controllerName' => $controller->getName(), 'actionName' => $action->getName()))
        );

        if ($action->getInputType() !== ActionDefinition::INPUT_CALL) {
            $json['fields'] = $this->getFieldsSchema($action->getInputModel()->getEditableFields());
        }

        return $json;
    }

    /**
     * Returns the schema of the output part of an action
     * 
     * @param ControllerDefinition $controller
     * @param ActionDefinition $action
     * @return array
     */
    protected function getActionOutputSchema(ControllerDefinition $controller, ActionDefinition $action)
    {
        if ($action->getReturnType() === ActionDefinition::RETURN_NONE) {
            return array('type' => $action->getReturnType());
        }

        $json = array(
            'type' => $action->getReturnType(),
            'paginated' => $action->isPaginated(),
            'sortable' => $action->isSortable()
        );

        if ($action->isPaginated()) {
            $json['items_per_page'] = $action->getItemsPerPage();
        }

        $self = $this;
        $json['actions'] = array_merge(
            array_values(array_filter(array_map(function($modelAction) use ($controller, $action, $self) {
                if (!$self->accessControl->checkPermissions($modelAction->getPermissions())) {
                    return false;
                }
                return array_merge($self->formatAction($modelAction), array(
                    'controller' => $controller->getName(),
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
                    'controller' => $controller->getName(),
                    'url' => $self->routing->generate('dashboard.invoke', 
                        array('controllerName' => $controller->getName(), 'actionName' => $action->getName()))
                ));
            }, $controller->getActionsForModel($action->getReturnModel()->getClassName()))))
        );

        if ($action->getReturnType() === ActionDefinition::RETURN_FORM) {
            $fields = $action->getReturnModel()->getEditableFields();
            if ($idField = $action->getReturnModel()->getIdentifierField()) {
                if (!in_array($idField, $fields)) {
                    $fields[] = $idField;
                }
            }
            $json['fields'] = $this->getFieldsSchema($fields);
        } else {
            $json['fields'] = $this->getFieldsSchema($action->getReturnModel()->getListableFields());
        }

        if ($action->isPiped()) {
            $json['pipe'] = $action->getPipe();
            $json['url'] = $this->routing->generate('dashboard.actionSchema',
                array('controllerName' => $controller->getName(), 'actionName' => $action->getPipe()));
        }

        return $json;
    }

    /**
     * Returns the schema for an array of fields
     * 
     * @param array $fields
     * @return array
     */
    protected function getFieldsSchema($fields)
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
                'editable' => $f->isEditable(),
                'link' => $link
            );
        }, $fields));
    }

    /**
     * @\Nucleus\IService\Routing\Route(name="dashboard.invoke", path="/nucleus/dashboard/{controllerName}/{actionName}")
     */
    public function invokeAction($controllerName, $actionName, Request $request, Response $response)
    {
        list($controller, $action) = $this->getAction($controllerName, $actionName);

        $service = $this->serviceContainer->get($controller->getServiceName());
        $model = $action->getInputModel();
        $data = $this->getInputData($request);

        if ($action->isPaginated() && !$action->isAutoPaginated() && ($param = $action->getOffsetParam()) !== null) {
            if (isset($data['__offset'])) {
                $data[$param] = $data['__offset'];
                unset($data['__offset']);
            } else {
                $data[$param] = 0;
            }
        }

        if ($action->isSortable() && isset($data['__sort'])) {
            $data[$action->getSortFieldParam()] = $data['__sort'];
            unset($data['__sort']);
            if (isset($data['__sort_order'])) {
                if (($orderParam = $action->getSortOrderParam()) !== null) {
                    $data[$orderParam] = $data['__sort_order'];
                }
                unset($data['__sort_order']);
            }
        }

        try {
            if ($action->isModelOnlyArgument()) {
                if ($action->isModelLoaded()) {
                    $object = $this->loadModel($model, $data);
                } else {
                    $object = $this->instanciateModel($model, $data);
                }
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

        return $this->formatInvokedResponse($request, $action, $result);
    }

    /**
     * @\Nucleus\IService\Routing\Route(name="dashboard.invokeModel", path="/nucleus/dashboard/{controllerName}/{actionName}/{modelActionName}")
     */
    public function invokeModelAction($controllerName, $actionName, $modelActionName, Request $request, Response $response)
    {
        list($controller, $action, $modelAction) = $this->getAction($controllerName, $actionName, $modelActionName);

        $data = $this->getInputData($request);
        $model = $action->getReturnModel();
        $object = $this->loadModel($model, $data);

        try {
            $model->validate($object);
        } catch (ValidationException $e) {
            return $this->formatErrorResponse((string) $e->getVioliations());
        }

        $result = $this->invoker->invoke(
            array($object, $modelAction->getName()), array(), array($request, $response));

        return $this->formatInvokedResponse($request, $modelAction, $result, $object);
    }

    /**
     * Returns the ControllerDefinition and ActionDefinition based on their names
     * 
     * @param string $controllerName
     * @param string $actionName
     * @param string $modelActionName
     * @return array
     */
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

    /**
     * Returns the input data
     * 
     * @param Request $request
     * @return array
     */
    protected function getInputData(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            return json_decode($request->request->get('data'), true);
        }
        return $request->query->all();
    }

    /**
     * Formats a successful response
     * 
     * @param mixed $data
     * @return array
     */
    protected function formatResponse($data)
    {
        return array('success' => true, 'data' => $data);
    }

    /**
     * Formats an error response
     * 
     * @param string $message
     * @return array
     */
    protected function formatErrorResponse($message)
    {
        return array('success' => false, 'message' => $message);
    }

    /**
     * Formats the result of an invoked action to be used as the query response
     * 
     * @param Request $request
     * @param ActionDefinition $action
     * @param mixed $result
     * @param object $obj The object used if this was a model action
     * @return array                 
     */
    protected function formatInvokedResponse(Request $request, ActionDefinition $action, $result, $obj = null)
    {
        $model = $action->getReturnModel();
        if ($action->getReturnType() === ActionDefinition::RETURN_LIST) {
            $count = null;
            if ($action->isAutoPaginated() && $result !== null) {
                if (!($result instanceof Iterator) && !is_array($result)) {
                    throw new DashboardException("List results expect an array or an Iterator, '" . get_class($result) . "' given");
                }
                list($count, $result) = $this->autoPaginateResults($request, $result);
            } else if ($action->isPaginated() && $result !== null) {
                $count = $result[0];
                $result = $result[1];
            }

            $data = array();
            if ($result !== null) {
                foreach ($result as $item) {
                    $data[] = $this->convertObjectToJson($item, $model);
                }
            }

            if ($action->isPaginated()) {
                $data = array('count' => $count, 'data' => $data, 'per_page' => $action->getItemsPerPage());
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

    /**
     * Paginate results using a LimitIterator
     * 
     * @param Request $request
     * @param $result
     * @return array
     */
    protected function autoPaginateResults(Request $request, $result)
    {
        $count = null;
        if (is_array($result)) {
            $result = new ArrayIterator($result);
        }
        if ($result instanceof Countable) {
            $count = $result->count();
        }
        $result = new LimitIterator($result, $request->query->get('__offset', 0), $request->query->get('__limit', 1));
        return array($count, $result);
    }

    /**
     * Converts an object to JSON according to the ModelDefinition
     * @param object $obj
     * @param ModelDefinition $model
     * @return array
     */
    protected function convertObjectToJson($obj, ModelDefinition $model)
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

    /**
     * Loads a model using the $data according to the ModelDefinition (eg: using the model loader)
     * 
     * @param ModelDefinition $model
     * @param array $data
     * @return object
     */
    protected function loadModel(ModelDefinition $model, $data)
    {
        if (!$model->hasLoader()) {
            return $this->instanciateModel($model, $data);
        }
        $loaderArgs = $data;
        if ($idField = $model->getIdentifierField()) {
            $loaderArgs = $data[$idField->getProperty()];
        }
        $obj = call_user_func($model->getLoader(), $loaderArgs);
        $this->populateModel($obj, $model, $data);
        return $obj;
    }

    /**
     * Creates a new object according to the ModelDefinition
     * 
     * @param ModelDefinition $model
     * @param array $data
     * @return object
     */
    protected function instanciateModel(ModelDefinition $model, $data = array())
    {
        $class = new ReflectionClass($model->getClassName());
        $obj = $class->newInstance();
        $this->populateModel($obj, $model, $data, $class);
        return $obj;
    }

    /**
     * Populates an object with the specified data according to the given ModelDefinition
     * 
     * @param object $obj
     * @param ModelDefinition $model
     * @param array $data
     * @param ReflectionClass $class
     * @return object
     */
    protected function populateModel($obj, ModelDefinition $model, $data, ReflectionClass $class = null)
    {
        if ($class === null) {
            $class = new ReflectionClass($obj);
        }
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
