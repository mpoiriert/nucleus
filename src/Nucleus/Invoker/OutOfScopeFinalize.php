<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Martin
 * Date: 13-11-28
 * Time: 14:55
 * To change this template use File | Settings | File Templates.
 */

namespace Nucleus\Invoker;


class OutOfScopeFinalize
{
    private $callback;

    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    public function __destruct()
    {
        call_user_func($this->callback);
    }
}