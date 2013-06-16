<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService;

/**
 * Description of FunctionsTest
 *
 * @author Martin
 */
class FunctionsTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider provideUsortMultiLevel
     * 
     * @param array $toSort
     * @param array $callbacks
     * @param array $expected
     */
    public function testUsort_multi_level(array $toSort, array $callbacks, array $expected)
    {
        usort_multi_level($toSort, $callbacks);
        $this->assertSame($expected, $toSort);
    }

    public function provideUsortMultiLevel()
    {
        return array(
            array(array(4, 5, 3, 6, 2, 1, 7), array(function($a, $b) {
                        return $a - $b;
                    }), array(1, 2, 3, 4, 5, 6, 7)),
            array(
                array(array('a' => 1, 'b' => 2), array('a' => 1, 'b' => 1), array('a' => 2, 'b' => 2), array('a' => 2, 'b' => 1)),
                array(function($a, $b) {
                        return $a['a'] - $b['a'];
                    }, function($a, $b) {
                        return $a['b'] - $b['b'];
                    }),
                array(array('a' => 1, 'b' => 1), array('a' => 1, 'b' => 2), array('a' => 2, 'b' => 1), array('a' => 2, 'b' => 2)),
            ),
            array(
                array(array('a' => 2, 'b' => 2, 'c' => 2), array('a' => 1, 'b' => 1, 'c' => 3), array('a' => 2, 'b' => 1, 'c' => 1), array('a' => 1, 'b' => 1, 'c' => 2)),
                array(function($a, $b) {
                        return $a['a'] - $b['a'];
                    }, function($a, $b) {
                        return $a['b'] - $b['b'];
                    }, function($a, $b) {
                        return $a['c'] - $b['c'];
                    }),
                array(array('a' => 1, 'b' => 1, 'c' => 2), array('a' => 1, 'b' => 1, 'c' => 3), array('a' => 2, 'b' => 1, 'c' => 1), array('a' => 2, 'b' => 2, 'c' => 2)),
            ),
        );
    }
}
