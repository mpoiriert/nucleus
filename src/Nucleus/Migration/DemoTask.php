<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Martin
 * Date: 13-10-03
 * Time: 14:21
 * To change this template use File | Settings | File Templates.
 */

namespace Nucleus\Migration;


class DemoTask extends BaseMigrationTask
{
    public function run()
    {
        echo $this->parameters['value'];
    }
}