<?php

namespace Nucleus\Dashboard\Bridge\Propel;

use OMBuilder;

class DashboardBaseControllerBehaviorBuilder extends OMBuilder
{
    public function getParameter($name)
    {
        if ($this->getTable()->hasBehavior('dashboard_parent_controller')) {
            return $this->getTable()->getBehavior('dashboard_parent_controller')->getParameter($name);
        }
        return $this->getTable()->getBehavior('dashboard_controller')->getParameter($name);
    }

    public function getUnprefixedClassname()
    {
        return 'Base' . $this->getStubObjectBuilder()->getUnprefixedClassname() . 'DashboardController';
    }

    public function getPackage()
    {
        return $this->getStubObjectBuilder()->getPackage() . '.om';
    }

    public function getNamespace()
    {
        return $this->getStubObjectBuilder()->getNamespace() . '\om';
    }

    protected function addClassOpen(&$script)
    {
        $script .= "
abstract class " . $this->getClassname() . "
{
";
    }

    protected function addClassBody(&$script)
    {
        $script .= $this->addListAction();

        if ($this->getParameter('is_concrete_parent') == 'true') {
            $script .= $this->addConcreteInheritanceViewAction();
        } else {
            $script .= $this->addViewAction();
            if ($this->getParameter('edit') == 'true') {
                $script .= $this->addAddAction() . $this->addEditAction();
            }
        }
    }

    protected function addListAction()
    {
        $objectClassname = $this->getStubObjectBuilder()->getFullyQualifiedClassname();
        $queryClassname = $this->getStubQueryBuilder()->getFullyQualifiedClassname();
        $perPage = $this->getParameter('items_per_page');

        $pk = $this->getTable()->getPrimaryKey();
        $orderBy = $pk[0]->getPhpName();

        $additionalAnnotations = array();
        if ($secureAnnotation = $this->getSecureAnnotation()) {
            $additionalAnnotations[] = $secureAnnotation;
        }
        if ($this->getTable()->hasBehavior('sortable')) {
            $additionalAnnotations[] = '@\Nucleus\IService\Dashboard\ActionBehavior(class="Nucleus\Dashboard\Bridge\Propel\SortableActionBehavior")';
            $orderBy = $this->getTable()->getBehavior('sortable')->getParameter('rank_column');
        }

        return "
    /**
     * @\Nucleus\IService\Dashboard\Action(title=\"List\", icon=\"list\", default=true)
     * @\Nucleus\IService\Dashboard\Paginate(per_page={$perPage}, offset_param=\"offset\")
     * @\Nucleus\IService\Dashboard\Orderable(param=\"order_by\", order_param=\"order_by_direction\")
     * @\Nucleus\IService\Dashboard\Filterable(param=\"filters\")
     * " . implode("\n     * ", $additionalAnnotations) . "
     * @return \\{$objectClassname}[]
     */
    public function listAll(array \$filters = array(), \$offset = 0, \$order_by= '{$orderBy}', \$order_by_direction = 'asc')
    {
        \$items = \\{$queryClassname}::create()
                ->_or()
                ->filterByArray(\$filters)
                ->orderBy(\$order_by, \$order_by_direction);

        if (\$offset >= 0) {
            \$items = \$items->paginate((\$offset / {$perPage}) + 1, {$perPage});
            return array(\$items->getNbResults(), \$items->getResults());
        } else {
            return array(null, \$items->find());
        }
    }
";
    }

    protected function addAddAction()
    {
        $objectClassname = $this->getStubObjectBuilder()->getFullyQualifiedClassname();
        $queryClassname = $this->getStubQueryBuilder()->getFullyQualifiedClassname();
        $secureAnnotation = $this->getSecureAnnotation();
        return "
    /**
     * @\Nucleus\IService\Dashboard\Action(title=\"Add\", icon=\"plus\", redirect_with_id=\"edit\")
     * {$secureAnnotation}
     * @return \\{$objectClassname}
     */
    public function add(\\{$objectClassname} \$obj)
    {
        \$obj->save();
        return \$obj;
    }
";
    }

    protected function addViewAction()
    {
        $objectClassname = $this->getStubObjectBuilder()->getFullyQualifiedClassname();
        $queryClassname = $this->getStubQueryBuilder()->getFullyQualifiedClassname();
        $secureAnnotation = $this->getSecureAnnotation();
        list($funcargs, $callargs) = $this->getPrimaryKeyAsArgs();

        return "
    /**
     * @\Nucleus\IService\Dashboard\Action(title=\"View\", menu=false)
     * {$secureAnnotation}
     * @return \\{$objectClassname}
     */
    public function view({$funcargs})
    {
        return \\{$queryClassname}::create()->findPK({$callargs});
    }
";
    }

    protected function addConcreteInheritanceViewAction()
    {
        $objectClassname = $this->getStubObjectBuilder()->getFullyQualifiedClassname();
        $queryClassname = $this->getStubQueryBuilder()->getFullyQualifiedClassname();
        $secureAnnotation = $this->getSecureAnnotation();
        list($funcargs, $callargs) = $this->getPrimaryKeyAsArgs();

        return "
    /**
     * @\Nucleus\IService\Dashboard\Action(title=\"View\", menu=false)
     * {$secureAnnotation}
     * @return \\{$objectClassname}
     */
    public function view({$funcargs})
    {
        \$o = \\{$queryClassname}::create()->findPK({$callargs});
        return \$o->getChildObject();
    }
";
    }

    protected function addEditAction()
    {
        $objectClassname = $this->getStubObjectBuilder()->getFullyQualifiedClassname();
        $queryClassname = $this->getStubQueryBuilder()->getFullyQualifiedClassname();
        $secureAnnotation = $this->getSecureAnnotation();
        list($funcargs, $callargs) = $this->getPrimaryKeyAsArgs();

        return "
    /**
     * @\Nucleus\IService\Dashboard\Action(title=\"Edit\", icon=\"edit\", in=\"call\", on_model=\"{$objectClassname}\", pipe=\"save\")
     * {$secureAnnotation}
     * @return \\{$objectClassname}
     */
    public function edit({$funcargs})
    {
        return \\{$queryClassname}::create()->findPK({$callargs});
    }

    /**
     * @\Nucleus\IService\Dashboard\Action(menu=false, load_model=true)
     * {$secureAnnotation}
     * @return \\{$objectClassname}
     */
    public function save(\\{$objectClassname} \$obj)
    {
        \$obj->save();
        return \$obj;
    }
";
    }

    protected function getPrimaryKeyAsArgs()
    {
        $pks = array();
        foreach ($this->getTable()->getPrimaryKey() as $pk) {
            $pks[] = $pk->getPhpName();
        }

        $funcargs = '$' . implode(', $', $pks);

        if (count($pks) > 1) {
            $callargs = 'array($' . implode(', $', $pks) . ')';
        } else {
            $callargs = '$' . $pks[0];
        }

        return array($funcargs, $callargs);
    }

    protected function getSecureAnnotation()
    {
        if ($creds = $this->getParameter('credentials')) {
            return '@\Nucleus\IService\Security\Secure(permissions="' . $creds . '")';
        }
        return '';
    }

    protected function addClassClose(&$script)
    {
        $script .= "
}";
    }
}