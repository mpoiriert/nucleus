<?php

namespace Nucleus\Dashboard\Bridge\Propel;

use Behavior;

class DashboardControllerBehavior extends Behavior
{
    protected $parameters = array(
        'items_per_page' => 20,
        'credentials' => null
    );

    protected $additionalBuilders = array(
        '\Nucleus\Dashboard\Bridge\Propel\DashboardBaseControllerBehaviorBuilder',
        '\Nucleus\Dashboard\Bridge\Propel\DashboardControllerBehaviorBuilder'
    );

    public function modifyTable()
    {
        $table = $this->getTable();
        if (!$table->hasBehavior('dashboard_model')) {
            $table->addBehavior(new DashboardModelBehavior());
        }
    }
}