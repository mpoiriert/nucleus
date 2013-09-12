<?php

namespace Nucleus\Dashboard\Bridge\Propel;

use OMBuilder;

class DashboardControllerBehaviorBuilder extends OMBuilder
{
    public function getUnprefixedClassname()
    {
        return $this->getStubObjectBuilder()->getUnprefixedClassname() . 'DashboardController';
    }

    public function getBaseClassname()
    {
        return $this->getStubObjectBuilder()->getUnprefixedClassname() . 'BaseDashboardController';
    }

    protected function addClassOpen(&$script)
    {
        $table = $this->getTable();
        $title = ucfirst($table->getName());
        $script .= "
/**
 * @\Nucleus\IService\Dashboard\Controller(title=\"$title\")
 */
class " . $this->getClassname() . " extends " . $this->getBaseClassname() . "
{
";
    }

    protected function addClassBody(&$script)
    {
    }

    protected function addClassClose(&$script)
    {
        $script .= "
}";
    }
}