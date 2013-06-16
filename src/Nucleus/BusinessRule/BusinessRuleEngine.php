<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\BusinessRule;

use Nucleus\IService\Invoker\IInvokerService;
use Nucleus\IService\DependencyInjection\IServiceContainer;
use Nucleus\Framework\Nucleus;
use ArrayObject;
use Symfony\Component\Yaml\Yaml;

/**
 * Description of BussinessRuleEngine
 *
 * @author Martin
 */
class BusinessRuleEngine
{
    private $rulesObject = array();
    private $rules = array();
    private $defaultRules = array();

    /**
     * @var \Symfony\Component\Yaml\Yaml
     */
    private $yamlParser;

    /**
     * @var \Nucleus\IService\Invoker\IInvokerService
     */
    private $invoker;

    /**
     * @var \Nucleus\IService\DependencyInjection\IServiceContainer
     */
    private $serviceContainer;

    /**
     * 
     * @param \Nucleus\IService\DependencyInjection\IServiceContainer $serviceContainer
     * @param \Nucleus\IService\Invoker\IInvokerService $invoker
     * @param \Symfony\Component\Yaml\Yaml $yamlParser
     * 
     * @Inject
     */
    public function initialize(
    IServiceContainer $serviceContainer, IInvokerService $invoker, Yaml $yamlParser
    )
    {
        $this->serviceContainer = $serviceContainer;
        $this->invoker = $invoker;
        $this->yamlParser = $yamlParser;
    }

    /**
     * 
     * @param array $configuration
     * 
     * @Inject(configuration="$")
     */
    public function setConfiguration(array $configuration)
    {
        if (isset($configuration['rules'])) {
            $this->rules = $configuration['rules'];
        }

        if (isset($configuration['defaultRules'])) {
            $this->defaultRules = $configuration['defaultRules'];
        }
    }

    public function setRule($rule, $object)
    {
        $this->rulesObject[$rule] = $object;
    }

    public function setDefaultRule($context, $ruleName, $parameterName)
    {
        $this->defaultRules[$context] = array(
            'rule' => $ruleName,
            'parameter' => $parameterName
        );
    }

    public function getFirstMatch($rules, $context = "default", array $contextParameters = array())
    {
        foreach ($rules as $index => $ruleComposition) {
            if ($this->check($ruleComposition, $context, $contextParameters)) {
                return $index;
            }
        }

        return null;
    }

    public function getAllMatches($rules, $context = "default", array $contextParameters = array())
    {
        $result = array();
        foreach ($rules as $index => $ruleComposition) {
            if ($this->check($ruleComposition, $context, $contextParameters)) {
                $result[] = $index;
            }
        }

        return $result;
    }

    public function check($ruleComposition, $context = "default", array $contextParameters = array())
    {
        $engine = $this;
        //This is to prevent the enforce method to have all the parameters
        //And also prevent to assign the parameter to the object
        $callback = function($rule) use ($engine, $contextParameters, $context) {
                return $engine->verifyRule($rule, $contextParameters, $context);
            };

        return $this->enforce($ruleComposition, $callback);
    }

    /**
     * This method should not be called directly
     * 
     * @param string $rule
     * @param array $contextParameters
     * @param string $context
     * @return boolean
     */
    public function verifyRule($rule, $contextParameters, $context)
    {
        list($ruleName, $ruleParameters) = $this->extractRuleParameters($rule);
        $ruleParameters = new ArrayObject($ruleParameters);
        $ruleObject = $this->getRule($ruleName, $context, $ruleParameters);
        return $this->invoker->invoke($ruleObject, $ruleParameters->getArrayCopy(), $contextParameters);
    }

    private function getRule($ruleName, $context, ArrayObject $parameters)
    {
        $ruleContext = null;
        if (strpos($ruleName, '\\') !== false) {
            list($ruleContext, $ruleName) = explode('\\', $ruleName);
        }

        if (is_null($ruleContext)) {
            $ruleContext = $context;
            if (!isset($this->rulesObject[$ruleContext . '\\' . $ruleName])) {
                if (!isset($this->rules[$ruleContext . '\\' . $ruleName])) {
                    $defaultRuleConfiguration = $this->getDefaultRuleName($context);
                    if ($defaultRuleConfiguration['parameter']) {
                        $parameters[$defaultRuleConfiguration['parameter']] = $ruleName;
                    }
                    $ruleName = $defaultRuleConfiguration['rule'];
                }
            }
        }

        if (!isset($this->rulesObject[$ruleContext . '\\' . $ruleName])) {
            $serviceName = $this->rules[$ruleContext . '\\' . $ruleName];
            $this->rulesObject[$ruleContext . '\\' . $ruleName] = $this->serviceContainer->getServiceByName($serviceName);
        }

        return $this->rulesObject[$ruleContext . '\\' . $ruleName];
    }

    private function getDefaultRuleName($context)
    {
        if (array_key_exists($context, $this->defaultRules)) {
            return $this->defaultRules[$context];
        }

        return array('rule' => null, 'parameter' => null);
    }

    private function extractRuleParameters($rule)
    {
        if (false !== $pos = strpos($rule, '{')) {
            list($rule, $parameterString) = explode('{', $rule, 2);
            $parameters = $this->yamlParser->parse('{' . $parameterString, true);
        } else {
            $parameters = array();
        }
        return array($rule, $parameters);
    }

    private function enforce($checks, $callback, $useAnd = true)
    {
        if (!is_array($checks)) {
            $not = false;
            if ($checks{0} == '!') {
                $not = true;
                $checks = substr($checks, 1);
            }
            $result = $callback($checks);
            return $not ? !$result : $result;
        }

        $test = true;
        foreach ($checks as $rule) {
            // recursively check the rule with a switched AND/OR mode
            $test = $this->enforce($rule, $callback, $useAnd ? false : true);
            if (!$useAnd && $test) {
                return true;
            }

            if ($useAnd && !$test) {
                return false;
            }
        }
        return $test;
    }

    /**
     * @param mixed $configuration
     * @return BusinessRuleEngine
     */
    public static function factory($configuration = null)
    {
        if (is_null($configuration)) {
            $configuration = __DIR__ . '/nucleus.json';
        }

        return Nucleus::serviceFactory($configuration, 'businessRuleEngine');
    }
}
