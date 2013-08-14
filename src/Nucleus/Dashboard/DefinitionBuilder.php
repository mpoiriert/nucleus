<?php

namespace Nucleus\Dashboard;

use Nucleus\IService\DependencyInjection\IServiceContainer;
use Nucleus\Annotation\ParsingResult as AnnotationParsingResult;
use Nucleus\Annotation\AnnotationParser;
use ReflectionClass;
use ReflectionProperty;
use ReflectionMethod;
use Symfony\Component\Validator\Validation;

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

    public function buildController($className)
    {
        if (is_object($className)) {
            $className = get_class($className);
        }

        $annotations =  $this->parseAnnotations($className);
        $class = new ReflectionClass($className);
        $controller = new ControllerDefinition();
        $controller->setClassName($className);
        
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

        $controller->setActions($this->extractActionsFromClass($class, $annotations));

        return $controller;
    }

    public function buildModel($className)
    {
        $annotations = $this->parseAnnotations($className);
        $class = new ReflectionClass($className);
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
                if (!$anno->property) {
                    throw new DefinitionBuilderException("Field '{$anno->name}' of model '$className' is missing the 'property' attribute");
                }
                $classProperties[$anno->property] = $anno;
            }
        }

        $fields = array();
        foreach ($class->getProperties() as $property) {
            $annos = $annotations->getPropertyAnnotations($property->getName(), array(function($a) {
                return $a instanceof \Nucleus\IService\Dashboard\ModelField;
            }));
            if (empty($annos)) {
                if (!isset($classProperties[$property->getName()])) {
                    continue;
                }
                $anno = $classProperties[$property->getName()];
            } else {
                $anno = $annos[0];
                $anno->property = $property->getName();
            }
            $fields[$anno->property] = $this->buildField($anno, $property);
        }

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

    protected function extractActionsFromClass(ReflectionClass $class, AnnotationParsingResult $annotations)
    {
        $actions = array();
        foreach ($annotations->getAllMethodAnnotations() as $methodName => $annos) {
            $actionAnno = null;
            foreach ($annos as $anno) {
                if ($anno instanceof \Nucleus\IService\Dashboard\Action) {
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

    protected function buildAction(ReflectionMethod $method, $annotation = null, array $additionalAnnotations = array())
    {
        $action = new ActionDefinition();
        $action->setName($method->getName());

        if ($annotation) {
            $action->setTitle($annotation->title ?: $method->getName())
                   ->setIcon($annotation->icon)
                   ->setDefault($annotation->default)
                   ->setVisible($annotation->visible)
                   ->setLoadModel($annotation->load_model);

            if ($annotation->pipe) {
                $action->setPipe($annotation->pipe);
            }

            if ($annotation->on_model) {
                $action->applyToModel($annotation->on_model);
            }
        }

        if (!$annotation || $annotation->in === null) {
            if ($method->getNumberOfParameters() > 0) {
                $action->setInputType(ActionDefinition::INPUT_FORM);
            }
        } else {
            $action->setInputType($annotation->in);
        }

        if ($action->getInputType() !== ActionDefinition::INPUT_CALL) {
            $inputModel = $this->buildModelFromMethod($method, $additionalAnnotations);
            if ($method->getNumberOfParameters() == 1) {
                $fields = $inputModel->getFields();
                if (count($fields) === 1 && $fields[0]->isModelType()) {
                    $action->setModelOnlyArgument($fields[0]->getName());
                    $inputModel = $fields[0]->getModel();
                }
            }
            $action->setInputModel($inputModel);
        }

        if (!preg_match('/@return ([a-zA-Z\\\\]+)(\[\])?/', $method->getDocComment(), $returnTag)) {
            $returnTag = false;
        }

        if (!$annotation || (!$annotation->pipe && $annotation->out === null)) {
            if ($returnTag) {
                $action->setReturnType(isset($returnTag[2]) ? ActionDefinition::RETURN_LIST : ActionDefinition::RETURN_OBJECT);
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

        foreach ($additionalAnnotations as $anno) {
            if ($anno instanceof \Nucleus\IService\Security\Secure) {
                $yamlParser = $this->serviceContainer->get('yamlParser');
                $perms = $yamlParser->parse($anno->permissions);
                $action->setPermissions($perms);
            }
        }

        return $action;
    }

    protected function buildModelFromMethod(ReflectionMethod $method, array $additionalAnnotations = array())
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

            $model->addField($field);
        }

        $this->applyFieldConstraintsFromAnnotations($model, $additionalAnnotations);

        return $model;
    }

    protected function applyFieldConstraintsFromAnnotations(ModelDefinition $model, array $annotations)
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

            $model->getField($anno->property)->addConstraint(new $className(json_decode($anno->options, true)));
        }
    }

    protected function buildField($annotation, ReflectionProperty $property = null)
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
              ->setLink($annotation->link);

        if ($annotation->formField !== null) {
            $field->setFormFieldType($annotation->formField);
        }

        if ($annotation->type === null) {
            if (preg_match('/@var ([a-zA-Z]+)/', $property->getDocComment(), $results)) {
                $field->setType($results[1]);
            } else {
                $field->setType('string');
            }
        }

        return $field;
    }

    protected function parseAnnotations($className)
    {
        $annotationParser = new AnnotationParser();
        return $annotationParser->parse($className);
    }
}
