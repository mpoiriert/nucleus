<?php

namespace Nucleus\Dashboard;

use Nucleus\IService\DependencyInjection\IServiceContainer;
use Nucleus\Annotation\ParsingResult as AnnotationParsingResult;
use Nucleus\Annotation\AnnotationParser;
use ReflectionClass;
use ReflectionProperty;
use ReflectionMethod;
use Symfony\Component\Validator\Validation;
use Nucleus\Dashboard\ActionBehaviors\PaginatedBehavior;
use Nucleus\Dashboard\ActionBehaviors\OrderableBehavior;

/**
 * Builds Definition objects according to annotations
 */
class DefinitionBuilder
{
    protected $serviceContainer;

    protected $validator;

    /**
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function initialize(IServiceContainer $serviceContainer)
    {
        $this->serviceContainer = $serviceContainer;
        $this->validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();
    }

    /**
     * Builds a controller from a class name
     * 
     * @param string $className
     * @return ControllerDefinition
     */
    public function buildController($className)
    {
        if (is_object($className)) {
            $className = get_class($className);
        }

        $annotations =  $this->parseAnnotations($className);
        $class = new ReflectionClass($className);
        $controller = new ControllerDefinition();
        $controller->setClassName($className);
        
        // searches for the Controller annotation
        $annos = $annotations->getClassAnnotations(array(function($a) {
            return $a instanceof \Nucleus\IService\Dashboard\Controller;
        }));

        if (!empty($annos)) {
            $anno = $annos[0];
            if ($anno->name) {
                $controller->setName($anno->name);
            }
            if ($anno->title) {
                $controller->setTitle($anno->title);
            }
        }

        // extract actions
        $actions = $this->extractActionsFromClass($class, $annotations);

        // sets the default menu to be the controller's title
        foreach ($actions as $action) {
            if ($action->isVisible() && !$action->providesMenu()) {
                $action->setMenu($controller->getTitle());
            }
        }

        $controller->setActions($actions);

        return $controller;
    }

    /**
     * Builds a ModelDefinition from a class name
     * 
     * @param string $className
     * @return ModelDefinition
     */
    public function buildModel($className)
    {
        $class = new ReflectionClass($className);

        if ($class->hasMethod('getDashboardModelDefinition')) {
            $model = call_user_func(array($className, 'getDashboardModelDefinition'));
            if (!($model instanceof ModelDefinition)) {
                throw new DefinitionBuilderException("'$className::getDashboardModelDefinition()' must return a ModelDefinition object");
            }
            return $model;
        }

        $annotations = $this->parseAnnotations($className);
        $model = new ModelDefinition();
        $model->setClassName($className);

        $loader = null;
        $classProperties = array();
        foreach ($annotations->getClassAnnotations() as $anno) {
            if ($anno instanceof \Nucleus\IService\Dashboard\Model) {
                if ($anno->loader !== null && !is_callable($anno->loader)) {
                    throw new DefinitionBuilderException("Loader '{$anno->loader}' for model '$className' must be callable");
                }
                $loader = $anno->loader;
            } else if ($anno instanceof \Nucleus\IService\Dashboard\ModelField) {
                // class level field definition
                if (!$anno->property) {
                    throw new DefinitionBuilderException("Field '{$anno->name}' of model '$className' is missing the 'property' attribute");
                }
                $classProperties[$anno->property] = $anno;
            }
        }

        // extract fields from class properties
        $fields = array();
        foreach ($class->getProperties() as $property) {
            $annos = $annotations->getPropertyAnnotations($property->getName());
            $anno = null;
            foreach ($annos as $a) {
                if ($a instanceof \Nucleus\IService\Dashboard\ModelField) {
                    $anno = $a;
                    break;
                }
            }
            if ($anno === null) {
                if (!isset($classProperties[$property->getName()])) {
                    continue;
                }
                $anno = $classProperties[$property->getName()];
            } else {
                $anno->property = $property->getName();
            }
            $fields[$anno->property] = $this->buildField($anno, $property, $annos);
        }

        // adds fields from the class level annotations
        foreach ($classProperties as $property => $anno) {
            if (!isset($fields[$property])) {
                $fields[$property] = $this->buildField($anno);
            }
        }

        foreach ($annotations->getAllMethodAnnotations() as $methodName => $annos) {
            foreach ($annos as $anno) {
                if ($anno instanceof \Nucleus\IService\Dashboard\ModelLoader) {
                    $loader = array($className, $methodName);
                    break 2;
                }
            }
        }

        $model->setFields($fields)
              ->setActions($this->extractActionsFromClass($class, $annotations))
              ->setLoader($loader)
              ->setValidator($this->validator);

        return $model;
    }

    /**
     * Extracts actions from class methods
     * 
     * @param ReflectionClass $class
     * @param AnnotationParsingResult $annotations
     * @return array
     */
    protected function extractActionsFromClass(ReflectionClass $class, AnnotationParsingResult $annotations)
    {
        $actions = array();
        foreach ($annotations->getAllMethodAnnotations() as $methodName => $annos) {
            $actionAnno = null;
            foreach ($annos as $anno) {
                if ($anno instanceof \Nucleus\IService\Dashboard\Action) {
                    // they need to hae the Action annotation
                    $actionAnno = $anno;
                    break;
                }
            }
            if (!$actionAnno) {
                continue;
            }
            $actions[] = $this->buildAction($class->getMethod($methodName), $actionAnno, $annos);
        }
        return $actions;
    }

    /**
     * Creates an ActionDefinition from a class method
     * 
     * @param ReflectionMethod $method
     * @param Annotation $annotation
     * @param array $additionalAnnotations
     * @return ActionDefinition
     */
    protected function buildAction(ReflectionMethod $method, $annotation = null, array $additionalAnnotations = array())
    {
        $action = new ActionDefinition();
        $action->setName($method->getName());

        if ($annotation) {
            // the annotation is not mandatory but is useful to set some action properties
            $action->setTitle($annotation->title ?: $method->getName())
                   ->setIcon($annotation->icon)
                   ->setDefault($annotation->default)
                   ->setLoadModel($annotation->load_model);

            if ($annotation->menu !== null) {
                $action->setMenu($annotation->menu);
            }

            if ($annotation->pipe) {
                $action->setPipe($annotation->pipe);
            }

            if ($annotation->on_model) {
                $action->applyToModel($annotation->on_model);
            }
        }

        // some annotations use arguments which could trigger the creation
        // of an input model if their not ignore
        $minNbOfParams = 0;
        $excludeParams = array();

        // additional information provided by other annotations
        $yamlParser = $this->serviceContainer->get('yamlParser');
        foreach ($additionalAnnotations as $anno) {
            if ($anno instanceof \Nucleus\IService\Security\Secure) {
                $perms = $yamlParser->parse($anno->permissions);
                $action->setPermissions($perms);
            } else if ($anno instanceof \Nucleus\IService\Dashboard\Paginate) {
                $action->addBehavior(new PaginatedBehavior((array) $anno));
                if ($anno->offset_param !== null) {
                    $minNbOfParams++;
                    $excludeParams[] = $anno->offset_param;
                }
            } else if ($anno instanceof \Nucleus\IService\Dashboard\Orderable) {
                $action->addBehavior(new OrderableBehavior((array) $anno));
                $minNbOfParams++;
                $excludeParams[] = $anno->param;
                if ($anno->order_param !== null) {
                    $minNbOfParams++;
                    $excludeParams[] = $anno->order_param;
                }
            } else if ($anno instanceof \Nucleus\IService\Dashboard\ActionBehavior) {
                $classname = $anno->class;
                $params = $yamlParser->parse($anno->params);
                $action->addBehavior(new $classname($params));
            }
        }

        // input
        if (!$annotation || $annotation->in === null) {
            if ($method->getNumberOfParameters() > $minNbOfParams) {
                $action->setInputType(ActionDefinition::INPUT_FORM);
            }
        } else {
            $action->setInputType($annotation->in);
        }

        if ($action->getInputType() === ActionDefinition::INPUT_FORM || $method->getNumberOfParameters() > $minNbOfParams) {
            // builds the input model from the method's arguments
            $inputModel = $this->buildModelFromMethod($method, $additionalAnnotations, $excludeParams);
            if ($method->getNumberOfParameters() == $minNbOfParams + 1) {
                $fields = $inputModel->getFields();
                if (count($fields) === 1 && $fields[0]->isModelType()) {
                    $action->setModelOnlyArgument($fields[0]->getName());
                    $inputModel = $fields[0]->getModel();
                }
            }
            $action->setInputModel($inputModel);
        }

        // tries to determine the return type
        if (!preg_match('/@return ([a-zA-Z\\\\]+)(\[\])?/', $method->getDocComment(), $returnTag)) {
            $returnTag = false;
        }

        if (!$annotation || (!$annotation->pipe && $annotation->out === null)) {
            if ($returnTag) {
                $isArray = isset($returnTag[2]) || $returnTag[1] == 'array';
                $action->setReturnType($isArray ? ActionDefinition::RETURN_LIST : ActionDefinition::RETURN_OBJECT);
            }
        } else if (!$annotation->pipe) {
            $action->setReturnType($annotation->out);
        }

        if ($action->getReturnType() !== ActionDefinition::RETURN_NONE) {
            if ((!$annotation || $annotation->model === null) && !$returnTag) {
                throw new DefinitionBuilderException("Action '{$action->getName()}' returns something but has no model attached");
            }
            $action->setReturnModel($this->buildModel($annotation && $annotation->model ? $annotation->model : $returnTag[1]));
        }

        return $action;
    }

    /**
     * Builds a ModelDefinition from a method's argument
     * 
     * @param ReflectionMethod $method
     * @param array $additionalAnnotations
     * @param array $excludeParams
     * @return ModelDefinition
     */
    protected function buildModelFromMethod(ReflectionMethod $method, array $additionalAnnotations = array(), array $excludeParams = array())
    {
        $model = new ModelDefinition();
        $model->setValidator(Validation::createValidator());

        $paramComments = array();
        if (preg_match_all('/@param ([a-zA-Z\\\\]+) (\$[a-zA-Z_0-9]+)( .+)?$/m', $method->getDocComment(), $results)) {
            for ($i = 0, $c = count($results[0]); $i < $c; $i++) {
                $paramComments[$results[2][$i]] = array(
                    'type' => $results[1][$i],
                    'description' => trim($results[3][$i])
                );
            }
        }

        foreach ($method->getParameters() as $param) {
            if (in_array($param->getName(), $excludeParams)) {
                continue;
            }
            $com = isset($paramComments[$param->getName()]) ? $paramComments[$param->getName()] : null;

            if (!$com) {
                if ($type = $param->getClass()) {
                    $type = $type->getName();
                }
            } else {
                $type = $com['type'];
            }

            if (class_exists($type)) {
                $type = $this->buildModel($type);
            }

            $field = new FieldDefinition();
            $field->setProperty($param->getName())
                  ->setType($type)
                  ->setDescription($com ? $com['description'] : null)
                  ->setOptional($param->isOptional())
                  ->setEditable(true);

            $validateAnnotations = array_filter($additionalAnnotations, function($a) use ($param) {
                return ($a instanceof \Nucleus\IService\Dashboard\Validate) && ($a->property == $param->getName());
            });
            $this->applyFieldConstraintsFromAnnotations($field, $validateAnnotations);

            $model->addField($field);
        }

        return $model;
    }

    /**
     * Builds a FieldDefinition from a property
     * 
     * @param Annotation $annotation
     * @param ReflectionProperty $property
     * @return FieldDefinition
     */
    protected function buildField($annotation, ReflectionProperty $property = null, array $additionalAnnotations = array())
    {
        $field = new FieldDefinition();
        $field->setProperty($annotation->property)
              ->setName($annotation->name ?: $annotation->property)
              ->setDescription($annotation->description)
              ->setType($annotation->type)
              ->setIdentifier($annotation->identifier)
              ->setListable($annotation->listable)
              ->setEditable($annotation->editable)
              ->setOptional(!$annotation->required)
              ->setLink($annotation->link)
              ->setGetterSetterMethodNames($annotation->getter, $annotation->setter);

        if ($annotation->formField !== null) {
            $field->setFormFieldType($annotation->formField);
        }

        if (($property !== null && !$property->isPublic()) || $annotation->getter !== null || $annotation->setter !== null) {
            $field->setAccessMethod(FieldDefinition::ACCESS_GETTER_SETTER);
        }

        $this->applyFieldConstraintsFromAnnotations($field, $additionalAnnotations);

        if ($annotation->type === null) {
            if ($property !== null && preg_match('/@var ([a-zA-Z]+)/', $property->getDocComment(), $results)) {
                $field->setType($results[1]);
            } else {
                $field->setType('string');
            }
        }

        return $field;
    }

    /**
     * Adds validation constaints to a field according to annotations
     * 
     * @param FieldDefinition $field
     * @param array $annotations
     * @return
     */
    protected function applyFieldConstraintsFromAnnotations(FieldDefinition $field, array $annotations)
    {
        foreach ($annotations as $anno) {
            if (!($anno instanceof \Nucleus\IService\Dashboard\Validate)) {
                continue;
            }

            if (strpos($anno->constraint, '\\') !== false && class_exists($anno->constraint)) {
                $className = (string) $anno->constraint;
            } else {
                $className = 'Symfony\\Component\\Validator\\Constraints\\' . $anno->constraint;
            }

            $field->addConstraint(new $className(json_decode($anno->options, true)));
        }
    }

    protected function parseAnnotations($className)
    {
        $annotationParser = new AnnotationParser();
        return $annotationParser->parse($className);
    }
}
