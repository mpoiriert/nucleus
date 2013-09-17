<?php

namespace Nucleus\Dashboard\Bridge\Propel;

use OMBuilder;

class DashboardControllerBehaviorBuilder extends OMBuilder
{
    public $overwrite = false;
    
    public function getParameter($name)
    {
        if ($this->getTable()->hasBehavior('dashboard_parent_controller')) {
            return $this->getTable()->getBehavior('dashboard_parent_controller')->getParameter($name);
        }
        return $this->getTable()->getBehavior('dashboard_controller')->getParameter($name);
    }

    public function getUnprefixedClassname()
    {
        return $this->getStubObjectBuilder()->getUnprefixedClassname() . 'DashboardController';
    }

    public function getBaseClassname()
    {
        return 'Base' . $this->getStubObjectBuilder()->getUnprefixedClassname() . 'DashboardController';
    }

    protected function addClassOpen(&$script)
    {
        $this->declareClass($this->getNamespace() . '\om\\' . $this->getBaseClassname());
        $table = $this->getTable();

        if (($menu = $this->getParameter('menu')) === null) {
            $menu = ucfirst(str_replace('_', ' ', $table->getName()));
        }
        if ($this->getParameter('is_concrete_parent') == 'true') {
            $menu = 'false';
        }
        if ($menu != 'false') {
            $menu = '"' . $menu . '"';
        }

        $script .= "
/**
 * @\Nucleus\IService\Dashboard\Controller(menu=$menu)
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