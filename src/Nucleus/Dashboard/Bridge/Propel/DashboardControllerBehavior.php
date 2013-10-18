<?php

namespace Nucleus\Dashboard\Bridge\Propel;

use Behavior;

class DashboardControllerBehavior extends Behavior
{
    protected $name = 'dashboard_controller';

    protected $parameters = array(
        'items_per_page' => 50,
        'credentials' => null,
        'menu' => null,
        'autolist' => 'true',
        'edit' => 'true',
        'is_concrete_parent' => 'false'
    );

    protected $additionalBuilders = array(
        '\Nucleus\Dashboard\Bridge\Propel\DashboardBaseControllerBehaviorBuilder',
        '\Nucleus\Dashboard\Bridge\Propel\DashboardControllerBehaviorBuilder'
    );

    public function getListParameter($name)
    {
        return array_filter(array_map('trim', explode(',', $this->getParameter($name))));
    }

    public function modifyTable()
    {
        $table = $this->getTable();
        if (!$table->hasBehavior('dashboard_model')) {
            $table->addBehavior(new DashboardModelBehavior());
        }

        if ($table->hasBehavior('concrete_inheritance')) {
            $b = $table->getBehavior('concrete_inheritance');
            $parent = $table->getDatabase()->getTable($b->getParameter('extends'));
            $c = new DashboardParentControllerBehavior();
            $c->addParameter(array('name' => 'is_concrete_parent', 'value' => 'true'));
            $parent->addBehavior($c);
        }

    }
}