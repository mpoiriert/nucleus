<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\BusinessRuleEngine\Tests;

use Nucleus\BusinessRule\BusinessRuleEngine;

/**
 * Description of FileSystemLoader
 *
 * @author Martin
 */
class BusinessRuleEngineTest extends \PHPUnit_Framework_TestCase
{
    private $businessRuleEngine = null;

    public function setUp()
    {
        $this->businessRuleEngine = BusinessRuleEngine::factory();
    }

    /**
     * @dataProvider provideRuleCompositions
     */
    public function testCheck($ruleCompositions, $expected)
    {
        $objectBoolean = new TestBoolean();
        $this->businessRuleEngine->setRule('test\\default', new TestDefaultTrueRule());
        $this->assertSame($expected, $this->businessRuleEngine->check($ruleCompositions, 'test', array($objectBoolean)));
    }

    public function provideRuleCompositions()
    {
        return array(
            array(array('!default', 'default'), false),
            array('!default', false),
            array('default', true),
            array(array('default', 'default'), true),
            array(array(array('default', 'default')), true),
            array(array(array('!default', 'default')), true),
            array(array(array('!default', '!default')), false),
            array(array('default', array('!default', '!default')), false),
            array(array('default', array('!default', 'default')), true),
            array(array('!default', array('!default', 'default')), false),
        );
    }

    /**
     * @dataProvider provideDefaultRuleParameter
     */
    public function testDefaultRuleParameter(array $ruleNames, $rulesComposition, $expected)
    {
        $ruleNames = new TestRuleNames($ruleNames);
        $this->businessRuleEngine->setDefaultRule('test', 'bla', 'ruleName');
        $this->businessRuleEngine->setRule('test\\bla', new TestBlaRule());
        $this->assertSame($expected, $this->businessRuleEngine->check($rulesComposition, 'test', array($ruleNames)));
    }

    public function provideDefaultRuleParameter()
    {
        return array(
            array(array('toto'), array('toto'), true),
            array(array('test'), array('toto'), false),
            array(array('test'), array('!toto'), true),
            array(array('test'), array('!toto', 'test'), true),
            array(array('test'), array('toto', 'test'), false),
            array(array('test', 'toto'), array('toto', 'test'), true),
            array(array('test', 'toto'), array('toto', 'test', '!test'), false),
        );
    }

    public function testGetFirstMatch()
    {
        list($firstTrueIndex, $rules) = $this->prepareMultipleCheck();

        $objectBoolean = new TestBoolean();
        $this->businessRuleEngine->setRule('test\\default', new TestDefaultTrueRule());
        $this->assertSame(
            $firstTrueIndex, $this->businessRuleEngine->getFirstMatch($rules, 'test', array($objectBoolean))
        );
    }

    public function testGetAllMatches()
    {
        list(, $rules, $trueIndexes) = $this->prepareMultipleCheck();

        $objectBoolean = new TestBoolean();
        $this->businessRuleEngine->setRule('test\\default', new TestDefaultTrueRule());
        $this->assertSame(
            $trueIndexes, $this->businessRuleEngine->getAllMatches($rules, 'test', array($objectBoolean))
        );
    }

    protected function prepareMultipleCheck()
    {
        $firstTrueIndex = null;
        $rules = array();
        $trueIndexes = array();
        foreach ($this->provideRuleCompositions() as $index => $parameter) {
            list($rule, $expected) = $parameter;
            $rules[] = $rule;
            if ($expected) {
                $trueIndexes[] = $index;
            }
            if (is_null($firstTrueIndex) && $expected) {
                $firstTrueIndex = $index;
            }
        }

        return array($firstTrueIndex, $rules, $trueIndexes);
    }

    /**
     * @dataProvider provideMixContext
     * 
     * @param type $ruleCompositions
     * @param type $expected
     */
    public function testMixContext($ruleCompositions, $expected)
    {
        $objectBoolean = new TestBoolean();
        $this->businessRuleEngine->setRule('test\\default', new TestDefaultTrueRule());
        $this->businessRuleEngine->setRule('default\\true', new TestTrue());
        $this->businessRuleEngine->setRule('default\\false', new TestFalse());
        $this->assertSame($expected, $this->businessRuleEngine->check($ruleCompositions, 'default', array($objectBoolean)));
    }

    public function provideMixContext()
    {
        return array(
            array('true', true),
            array('false', false),
            array(array('false', 'test\\default'), false),
            array(array('!false', 'test\\default'), true),
            array(array('true', 'test\\default'), true),
        );
    }

    /**
     * @dataProvider provideTestWithParameters
     * 
     * @param type $ruleCompositions
     * @param type $expected
     */
    public function testWithParameters($ruleCompositions, $realParameters)
    {
        $exactMatch = new TestExactMatchValidator($realParameters);
        $this->businessRuleEngine->setRule('default\\allMatch', new TestExactMatchRule());
        $this->assertTrue($this->businessRuleEngine->check($ruleCompositions, 'default', array($exactMatch)));
    }

    public function provideTestWithParameters()
    {
        return array(
            array(array('allMatch{toto:else}'), array('toto' => 'else')),
            array(array('allMatch{value:false}'), array('value' => false)),
            array(array('allMatch{param1:[],param2:10}'), array('param1' => array(), 'param2' => 10))
        );
    }
}

class TestExactMatchValidator
{
    public $parameters;

    public function __construct($realParameters)
    {
        $this->parameters = $realParameters;
    }
}

class TestExactMatchRule
{

    public function __invoke(TestExactMatchValidator $validator, $toto = null, $value = null, $param1 = null, $param2 = null)
    {
        $parameters = compact('toto', 'value', 'param1', 'param2');
        foreach ($parameters as $parameter => $value) {
            if (array_key_exists($parameter, $validator->parameters)) {
                if ($validator->parameters[$parameter] !== $value) {
                    return false;
                }
                unset($validator->parameters[$parameter]);
            } elseif (!is_null($value)) {
                return false;
            }
        }
        return count($validator->parameters) === 0;
    }
}

class TestBoolean
{
    public $value = true;

}

class TestFalse
{

    public function __invoke()
    {
        return false;
    }
}

class TestTrue
{

    public function __invoke()
    {
        return true;
    }
}

class TestDefaultTrueRule
{

    public function __invoke(TestBoolean $testBoolean)
    {
        return $testBoolean->value === true;
    }
}

class TestRuleNames
{
    public $rules;

    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }
}

class TestBlaRule
{

    public function __invoke($ruleName, TestRuleNames $testRuleNames)
    {
        return in_array($ruleName, $testRuleNames->rules);
    }
}
