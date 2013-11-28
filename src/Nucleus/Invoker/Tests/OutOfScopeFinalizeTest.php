<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Martin
 * Date: 13-11-28
 * Time: 14:55
 * To change this template use File | Settings | File Templates.
 */

namespace Nucleus\Invoker\Tests;


class OutOfScopeFinalizeTest extends \PHPUnit_Framework_TestCase
{
   public function test()
   {
      $object = new \stdClass();
      $object->test = false;

      new \Nucleus\Invoker\OutOfScopeFinalize(function () use($object) {
          $object->test = true;
      });

      $this->assertTrue($object->test);
   }
}