<?php

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

class Dashboard
{
    /**
     * @var \Nucleus\Routing\Router 
     */
    public $routing;

    protected $serviceContainer;

    protected $initializeServices = array();

    protected $invoker;

    public $accessControl;

    protected $controllers = array();

    protected $lazyServiceControllers = array();

    protected $builder;
    
    protected $configuration;

    protected $throwExceptions = true;

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

        foreach ($this->initializeServices as $serviceName) {
            $this->addServiceAsController($serviceName);
        }
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

    public function addServiceAsController($serviceName, $name = null)
    {
        if ($name) {
            $this->lazyServiceControllers[$name] = $serviceName;
            return;
        }

        if ($this->serviceContainer === null) {
            $this->initializeServices[] = $serviceName;
            return;
        }

        $base = $this->buildBaseControllerDefinitionFromService($serviceName);
        $this->lazyServiceControllers[$base[0]->getName()] = $base;
    }

    protected function buildBaseControllerDefinitionFromService($name)
    {
        $service = $this->serviceContainer->get($name);
        $base = $this->builder->buildBaseController($service);
        $base[0]->setServiceName($name);
        return $base;
    }

    public function getController($name)
    {
        if (isset($this->lazyServiceControllers[$name])) {
            $base = $this->lazyServiceControllers[$name];
            if (is_string($base)) {
                $base = $this->buildBaseControllerDefinitionFromService($base);
            }
            $this->addController($this->builder->buildController($base));
        }
        return $this->controllers[$name];
    }

    public function getControllers()
    {
        if (!empty($this->lazyServiceControllers)) {
            foreach (array_keys($this->lazyServiceControllers) as $name) {
                $this->getController($name);
            }
        }
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
        foreach ($this->getControllers() as $controller) {
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
            return array_merge($self->getLimitedActionSchema($action), array(
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
            array(
                'input' => $this->getActionInputSchema($controller, $action),
                'output' => $this->getActionOutputSchema($controller, $action)
            )
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
            array(
                'name' => $action->getName() . '/' . $modelAction->getName(),
                'input' => array_merge(
                    $this->getActionInputSchema($controller, $action),
                    array('url' => $this->routing->generate('dashboard.invokeModel', array(
                        'controllerName' => $controller->getName(), 'actionName' => $action->getName(), 
                        'modelActionName' => $modelAction->getName()))
                    )
                ),
                'output' => $this->getActionOutputSchema($controller, $modelAction)
            )
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
            'type' => $action->getInputType()
        );

        if ($action->getFlow() === ActionDefinition::FLOW_DELEGATE) {
            $json['delegate'] = $this->routing->generate('dashboard.invoke', 
                array('controllerName' => $controller->getName(), 'actionName' => $action->getNextAction()));
        } else {
            $json['url'] = $this->routing->generate('dashboard.invoke', 
                array('controllerName' => $controller->getName(), 'actionName' => $action->getName()));
        }

        if (($model = $action->getInputModel()) !== null) {
            $json['model_name'] = $model->getName();
            $json['fields'] = $this->getFieldsSchema($model->getPublicFields());
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
        $json = array(
            'type' => $action->getReturnType(),
            'flow' => $action->getFlow()
        );

        if (!in_array($action->getFlow(), array(ActionDefinition::FLOW_NONE, ActionDefinition::FLOW_DELEGATE))) {
            $json['next_action'] = $action->getNextAction();
        }

        $limitedSchemaActions = array(ActionDefinition::RETURN_NONE, ActionDefinition::RETURN_REDIRECT, ActionDefinition::RETURN_FILE);
        if (in_array($action->getReturnType(), $limitedSchemaActions)) {
            return $json;
        }

        $json['behaviors'] = array();
        foreach ($action->getBehaviors() as $b) {
            $json['behaviors'][$b->getName()] = $b->getParams();
            if ($b->isInvokable()) {
                $json['behaviors'][$b->getName()]['url'] = $this->routing->generate('dashboard.invokeBehavior',
                    array('controllerName' => $controller->getName(), 'actionName' => $action->getName(), 'behaviorName' => $b->getName()));
            }
        }

        $self = $this;
        $json['actions'] = array_merge(
            array_values(array_filter(array_map(function($modelAction) use ($controller, $action, $self) {
                if (!$self->accessControl->checkPermissions($modelAction->getPermissions())) {
                    return false;
                }
                return array_merge($self->getLimitedActionSchema($modelAction), array(
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
                return array_merge($self->getLimitedActionSchema($action), array(
                    'controller' => $controller->getName(),
                    'url' => $self->routing->generate('dashboard.invoke', 
                        array('controllerName' => $controller->getName(), 'actionName' => $action->getName()))
                ));
            }, $controller->getActionsForModel($action->getReturnModel()->getClassName()))))
        );

        if (($model = $action->getReturnModel()) !== null) {
            $json['model_name'] = $model->getName();
            $json['fields'] = $this->getFieldsSchema($model->getPublicFields());
        }

        return $json;
    }

    /**
     * Returns the schema for an array of fields
     * 
     * @param array $fields
     * @return array
     */
    public function getFieldsSchema($fields, $recurse = true)
    {
        $self = $this;
        return array_values(array_map(function($f) use ($self, $recurse) {
            $valueController = null;
            if ($f->hasValueController()) {
                $valueController = array(
                    'controller' => $f->getValueController(),
                    'remote_id' => $f->getValueControllerRemoteId(),
                    'local_id' => $f->getValueControllerLocalId(),
                    'embed' => $f->isValueControllerEmbeded()
                );
            }
            $related = null;
            if ($m = $f->getRelatedModel()) {
                $related = array(
                    'name' => $m->getName(),
                    'identifier' => array_map(function($f) { return $f->getProperty(); }, $m->getIdentifierFields()),
                    'repr' => $m->getStringReprField()->getProperty(),
                    'controller' => $f->getRelatedModelController(),
                    'actions' => $f->getRelatedModelActions()
                );

                if ($recurse) {
                    $related['fields'] = $self->getFieldsSchema($m->getPublicFields(), false);
                }
            }
            return array(
                'type' => $f->getType(),
                'is_array' => $f->isArray(),
                'is_hash' => $f->isHash(),
                'field_type' => $f->getFormFieldType(),
                'field_options' => $f->getFormFieldOptions(),
                'formated_type' => $f->getFormatedType(),
                'name' => $f->getProperty(),
                'title' => $f->getName(),
                'description' => $f->getDescription(),
                'optional' => $f->isOptional(),
                'defaultValue' => $f->getDefaultValue(),
                'identifier' => $f->isIdentifier(),
                'visibility' => $f->getVisibility(),
                'related_model' => $related,
                'value_controller' => $valueController,
                'i18n' => $f->getI18n()
            );
        }, $fields));
    }

    /**
     * @\Nucleus\IService\Routing\Route(name="dashboard.invoke", path="/nucleus/dashboard/{controllerName}/{actionName}")
     */
    public function invokeAction($controllerName, $actionName, Request $request, Response $response)
    {
        try {

            list($controller, $action) = $this->getAction($controllerName, $actionName);

            $service = $this->serviceContainer->get($controller->getServiceName());
            $model = $action->getInputModel();
            $data = $this->getInputData($request);
            $params = array();

            if ($model !== null) {
                try {
                    if ($action->isModelLoaded()) {
                        $object = $model->loadObject($data);
                    } else {
                        $object = $model->instanciateObject($data);
                    }

                    $model->validateObject($object);

                    if ($action->isModelOnlyArgument()) {
                        $params = array($action->getModelArgumentName() => $object);
                    } else {
                        $params = $model->convertObjectToArray($object);
                    }
                } catch (ValidationException $e) {
                    return $this->formatErrorResponse((string) $e->getVioliations());
                }
            }

            $action->applyBehaviors('beforeInvoke', array($model, $data, &$params, $request, $response));

            $result = $this->invoker->invoke(
                array($service, $action->getName()), $params, array($request, $response));

            $action->applyBehaviors('afterInvoke', array($model, &$result, $request, $response));

            if ($result instanceof Response) {
                return $result;
            }
            return $this->formatInvokedResponse($request, $response, $action, $result);

        } catch (\Exception $e) {
            if ($this->throwExceptions) {
                throw $e;
            }
            return $this->formatErrorResponse($e->getMessage());
        }
    }

    /**
     * @\Nucleus\IService\Routing\Route(name="dashboard.invokeModel", path="/nucleus/dashboard/{controllerName}/{actionName}/{modelActionName}")
     */
    public function invokeModelAction($controllerName, $actionName, $modelActionName, Request $request, Response $response)
    {
        try {

            list($controller, $action, $modelAction) = $this->getAction($controllerName, $actionName, $modelActionName);

            $data = $this->getInputData($request);
            $model = $action->getReturnModel();
            $object = $model->loadObject($data);

            try {
                $model->validateObject($object);
            } catch (ValidationException $e) {
                return $this->formatErrorResponse((string) $e->getVioliations());
            }

            $action->applyBehaviors('beforeModelInvoke', array($model, $data, $request, $response));

            $result = $this->invoker->invoke(
                array($object, $modelAction->getName()), array(), array($request, $response));

            $action->applyBehaviors('afterModelInvoke', array($model, &$result, $request, $response));

            if ($result instanceof Response) {
                return $result;
            }
            return $this->formatInvokedResponse($request, $response, $modelAction, $result, $object);

        } catch (\Exception $e) {
            if ($this->throwExceptions) {
                throw $e;
            }
            return $this->formatErrorResponse($e->getMessage());
        }
    }

    /**
     * @\Nucleus\IService\Routing\Route(name="dashboard.invokeBehavior", path="/nucleus/dashboard/{controllerName}/{actionName}/_behaviors/{behaviorName}")
     */
    public function invokeBehavior($controllerName, $actionName, $behaviorName, Request $request, Response $response)
    {
        try {

            list($controller, $action) = $this->getAction($controllerName, $actionName);

            $data = $this->getInputData($request);
            if (($behavior = $action->getBehavior($behaviorName)) === null) {
                throw new DashboardException("Behavior '$behaviorName' on '$controllerName/$actionName' not found");
            }

            $result = $behavior->trigger('invoke', array($data, $request, $response));
            return $this->formatResponse($result);

        } catch (\Exception $e) {
            if ($this->throwExceptions) {
                throw $e;
            }
            return $this->formatErrorResponse($e->getMessage());
        }
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
        if ($request->getMethod() === 'GET') {
            return $request->query->all();
        }

        $data = json_decode($request->request->get('data'), true);

        foreach ($request->files->all() as $key => $file) {
            if ($file->isValid()) {
                $data[$key] = file_get_contents($file->getPathname());
            }
        }

        return $data;
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
     * @param Response $response
     * @param ActionDefinition $action
     * @param mixed $result
     * @param object $obj The object used if this was a model action
     * @return array                 
     */
    protected function formatInvokedResponse(Request $request, Response $response, ActionDefinition $action, $result, $obj = null)
    {
        $model = $action->getReturnModel();
        $data = null;

        if ($action->getReturnType() === ActionDefinition::RETURN_LIST) {
            $data = array();
            if ($result !== null) {
                foreach ($result as $item) {
                    $data[] = $model->convertObjectToArray($item);
                }
            }
        } else if ($action->getReturnType() === ActionDefinition::RETURN_FILE) {
            $filename = $action->getName();
            if (is_array($result)) {
                list($filename, $result) = $result;
            }
            $response->headers->set('Content-Type', 'application/octet-stream');
            $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
            $response->headers->set('Expires', '0');
            $response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
            $response->headers->set('Pragma', 'public');
            $response->headers->set('Content-Length', strlen($result));
            $response->setContent($result);
            return $response;
        } else if ($action->getReturnType() === ActionDefinition::RETURN_NONE) {
            $data = null;
        } else if ($result === null) {
            $data = null;
        } else if ($model) {
            $data = $model->convertObjectToArray($result);
        } else if ($result) {
            $data = $result;
        }

        $action->applyBehaviors('formatInvokedResponse', array(&$data));

        return $this->formatResponse($data);
    }
}
