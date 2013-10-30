<?php

namespace Nucleus\Dashboard\Bridge\Propel;

use OMBuilder;

class DashboardBaseControllerBehaviorBuilder extends OMBuilder
{
    public function getControllerBehavior()
    {
        if ($this->getTable()->hasBehavior('dashboard_controller')) {
            return $this->getTable()->getBehavior('dashboard_controller');
        }
        return $this->getTable()->getBehavior('dashboard_parent_controller');
    }

    public function getParameter($name)
    {
        return $this->getControllerBehavior()->getParameter($name);
    }

    public function getListParameter($name)
    {
        return $this->getControllerBehavior()->getListParameter($name);
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

    protected function getDefaultActionAnnotations()
    {
        $annotations = array();

        if ($creds = $this->getParameter('credentials')) {
            $annotations[] = '@\Nucleus\IService\Security\Secure(permissions="' . $creds . '")';
        }

        return $annotations;
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
        $script .= $this->addListAction()
                 . $this->addListReprAction();

        if ($this->getParameter('is_concrete_parent') == 'true') {
            $script .= $this->addConcreteInheritanceViewAction()
                     . $this->addEditRedirectAction();
        } else {
            $editable = $this->getParameter('edit') == 'true';
            $script .= $this->addViewAction(!$editable);
            if ($editable) {
                $script .= $this->addAddAction() . $this->addEditAction();
            } else {
                $script .= $this->addEditRedirectAction();
            }
        }

        if ($this->getTable()->hasBehavior('versionable')) {
            $script .= $this->addVersionHistoryAction();
        }

        if ($b = $this->getTable()->getBehavior('dashboard_model')) {
            foreach ($b->getChildrenFKs() as $fk) {
                $script .= $this->addChildActions($fk);
            }
        }
    }

    protected function addListAction()
    {
        $table = $this->getTable();
        $annotations = $this->getDefaultActionAnnotations();
        $objectClassname = $this->getStubObjectBuilder()->getFullyQualifiedClassname();
        $queryClassname = $this->getStubQueryBuilder()->getFullyQualifiedClassname();
        $perPage = $this->getParameter('items_per_page');
        $in = $this->getParameter('autolist') == 'true' ? 'call' : 'none';

        $annotations[] = "@\Nucleus\IService\Dashboard\Action(title=\"List\", icon=\"list\", default=true, in=\"$in\")";
        $annotations[] = "@\Nucleus\IService\Dashboard\Paginate(per_page={$perPage}, offset_param=\"offset\")";
        $annotations[] = "@\Nucleus\IService\Dashboard\Filterable(param=\"filters\")";

        if ($table->hasBehavior('sortable')) {
            $annotations[] = '@\Nucleus\IService\Dashboard\ActionBehavior(class="Nucleus\Dashboard\Bridge\Propel\SortableActionBehavior")';
            $orderargs = '';
            $orderby = 'orderByRank()';
        } else {
            $annotations[] = "@\Nucleus\IService\Dashboard\Orderable(param=\"order_by\", order_param=\"order_by_direction\")";
            $pk = $table->getPrimaryKey();
            $orderargs = ", \$order_by= '{$pk[0]->getPhpName()}', \$order_by_direction = 'asc'";
            $orderby = 'orderBy($order_by, $order_by_direction)';
        }

        return "
    /**
    " . $this->renderAnnotations($annotations) . "
     *
     * @return \\{$objectClassname}[]
     */
    public function listAll(array \$filters = array(), \$offset = 0{$orderargs})
    {
        \$items = \\{$queryClassname}::create()
                ->_or()
                ->filterByArray(\$filters)
                ->{$orderby};

        if (\$offset >= 0) {
            \$items = \$items->paginate((\$offset / {$perPage}) + 1, {$perPage});
            return array(\$items->getNbResults(), \$items->getResults());
        } else {
            return array(null, \$items->find());
        }
    }
";
    }

    protected function addListReprAction()
    {
        $table = $this->getTable();
        $queryClassname = $this->getStubQueryBuilder()->getFullyQualifiedClassname();
        $annotations = $this->getDefaultActionAnnotations();
        $pk = $table->getFirstPrimaryKeyColumn()->getPhpName();
        $repr = $table->getBehavior('dashboard_model')->getParameter('repr') ?: $pk;

        $annotations[] = "@\Nucleus\IService\Dashboard\Action(menu=false)";

        return "
    /**
    " . $this->renderAnnotations($annotations) . "
     *
     * @return array
     */
    public function listRepr()
    {
        \$items = \\{$queryClassname}::create()
            ->setFormatter('PropelStatementFormatter')
            ->select(array('{$pk}', '{$repr}'))
            ->find()
            ->fetchAll(\PDO::FETCH_KEY_PAIR);
        return \$items;
    }
";
    }

    protected function addAddAction()
    {
        $annotations = $this->getDefaultActionAnnotations();
        $objectClassname = $this->getStubObjectBuilder()->getFullyQualifiedClassname();
        $queryClassname = $this->getStubQueryBuilder()->getFullyQualifiedClassname();

        $annotations[] = "@\Nucleus\IService\Dashboard\Action(title=\"Add\", icon=\"plus\", redirect_with_id=\"edit\")";

        return "
    /**
    " . $this->renderAnnotations($annotations) . "
     *
     * @return \\{$objectClassname}
     */
    public function add(\\{$objectClassname} \$obj)
    {
        \$obj->save();
        return \$obj;
    }
";
    }

    protected function addViewAction($onModel = false)
    {
        $annotations = $this->getDefaultActionAnnotations();
        $objectClassname = $this->getStubObjectBuilder()->getFullyQualifiedClassname();
        $queryClassname = $this->getStubQueryBuilder()->getFullyQualifiedClassname();
        list($funcargs, $callargs) = $this->getPrimaryKeyAsArgs();

        $params = '';
        if ($onModel) {
            $params = ', on_model="' . $objectClassname . '"';
        }

        $annotations[] = "@\Nucleus\IService\Dashboard\Action(title=\"View\", icon=\"eye-open\", in=\"call\", menu=false $params)";

        return "
    /**
    " . $this->renderAnnotations($annotations) . "
     *
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
        $annotations = $this->getDefaultActionAnnotations();
        $objectClassname = $this->getStubObjectBuilder()->getFullyQualifiedClassname();
        $queryClassname = $this->getStubQueryBuilder()->getFullyQualifiedClassname();
        list($funcargs, $callargs) = $this->getPrimaryKeyAsArgs();

        $annotations[] = "@\Nucleus\IService\Dashboard\Action(title=\"View\", menu=false)";

        return "
    /**
    " . $this->renderAnnotations($annotations) . "
     *
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
        $annotations = $this->getDefaultActionAnnotations();
        $objectClassname = $this->getStubObjectBuilder()->getFullyQualifiedClassname();
        $queryClassname = $this->getStubQueryBuilder()->getFullyQualifiedClassname();
        list($funcargs, $callargs) = $this->getPrimaryKeyAsArgs();

        return "
    /**
     * @\Nucleus\IService\Dashboard\Action(title=\"Edit\", icon=\"edit\", in=\"call\", on_model=\"{$objectClassname}\", pipe=\"save\")
    " . $this->renderAnnotations($annotations) . "
     *
     * @return \\{$objectClassname}
     */
    public function edit({$funcargs})
    {
        return \\{$queryClassname}::create()->findPK({$callargs});
    }

    /**
     * @\Nucleus\IService\Dashboard\Action(menu=false, load_model=true)
    " . $this->renderAnnotations($annotations) . "
     *
     * @return \\{$objectClassname}
     */
    public function save(\\{$objectClassname} \$obj)
    {
        \$obj->save();
        return \$obj;
    }
";
    }

    protected function addEditRedirectAction()
    {
        $annotations = $this->getDefaultActionAnnotations();
        $objectClassname = $this->getStubObjectBuilder()->getFullyQualifiedClassname();
        $queryClassname = $this->getStubQueryBuilder()->getFullyQualifiedClassname();
        list($funcargs, $callargs) = $this->getPrimaryKeyAsArgs();

        return "
    /**
     * @\Nucleus\IService\Dashboard\Action(menu=false, redirect_with_id=\"view\")
    " . $this->renderAnnotations($annotations) . "
     *
     * @return \\{$objectClassname}
     */
    public function edit({$funcargs})
    {
        return \\{$queryClassname}::create()->findPK({$callargs});
    }
";
    }

    protected function addChildActions($fk)
    {
        $annotations = $this->getDefaultActionAnnotations();
        $table = $fk->getTable();
        $name = $table->getPhpName();
        $pname = $name . "s";

        $lcols = $fk->getLocalColumnObjects();
        $localId = $lcols[0]->getPhpName();
        list($funcargs, $callargs) = $this->getPrimaryKeyAsArgs($table, array($lcols[0]));

        $queryClassname = $this->getStubQueryBuilder()->getFullyQualifiedClassname();
        $childObjectClassname = $this->getNewStubObjectBuilder($table)->getFullyQualifiedClassname();
        $childQueryClassname = $this->getNewStubQueryBuilder($table)->getFullyQualifiedClassname();
        
        $childOrderBy = '';
        if ($table->hasBehavior('sortable')) {
            $childOrderBy = "\\{$childQueryClassname}::create()->orderByRank()";
        }

        return "

    /**
     * @\Nucleus\IService\Dashboard\Action(menu=false)
    " . $this->renderAnnotations($annotations) . "
     *
     * @return \\{$childObjectClassname}[]
     */
    public function list{$pname}(\${$localId})
    {
        \$obj = \\{$queryClassname}::create()->findPK(\${$localId});
        return \$obj->get{$pname}({$childOrderBy});
    }

    /**
     * @\Nucleus\IService\Dashboard\Action(menu=false)
    " . $this->renderAnnotations($annotations) . "
     */
    public function remove{$pname}(\${$localId}, {$funcargs})
    {
        \$obj = \\{$queryClassname}::create()->findPK(\${$localId});
        \$child = \\{$childQueryClassname}::create()->findPK({$callargs});
        \$obj->remove{$name}(\$child);
        \$obj->save();
    }
";
    }

    public function addVersionHistoryAction()
    {
        $objectClassname = $this->getStubObjectBuilder()->getFullyQualifiedClassname();
        $queryClassname = $this->getStubQueryBuilder()->getFullyQualifiedClassname();
        list($funcargs, $callargs) = $this->getPrimaryKeyAsArgs();

        $listAnnotations = $this->getDefaultActionAnnotations();
        $listAnnotations[] = "@\Nucleus\IService\Dashboard\Action(title=\"Show versions\", icon=\"random\", on_model=\"$objectClassname\", out=\"dynamic\")";

        $showAnnotations = $this->getDefaultActionAnnotations();
        $showAnnotations[] = "@\Nucleus\IService\Dashboard\Action(title=\"Show version\", menu=false, out=\"dynamic\")";

        $modelCode = "\$model = clone \\{$objectClassname}::getDashboardModelDefinition();
        \$model->setClassName('{$objectClassname}Version')
           ->setActions(array())
           ->addField(\\Nucleus\\Dashboard\\FieldDefinition::create()
             ->setProperty('Version')
             ->setIdentifier(true)
             ->setAccessMethod(\\Nucleus\\Dashboard\\FieldDefinition::ACCESS_GETTER_SETTER));";

        return "
    /**
    " . $this->renderAnnotations($listAnnotations) . "
     *
     * @return \\{$objectClassname}Version[]
     */
    public function listVersionHistory({$funcargs})
    {
        {$modelCode}

        \$model->addAction(\\Nucleus\\Dashboard\\ActionDefinition::create()
             ->setName('showVersion')
             ->setTitle('Show version')
             ->setIcon('random')
             ->applyToModel(false));

        \$action = new \\Nucleus\\Dashboard\\ActionDefinition();
        \$action->setName('listVersionHistory')
                ->setTitle('List Version History')
                ->setReturnType('list')
                ->setReturnModel(\$model);

        \$item = \\{$queryClassname}::create()->findPK({$callargs});
        return array(\$action, \$item->getAllVersions());
    }

    /**
    " . $this->renderAnnotations($showAnnotations) . "
     *
     * @return \\{$objectClassname}Version
     */
    public function showVersion({$funcargs}, \$Version)
    {
        {$modelCode}
        
        \$model->addAction(\\Nucleus\\Dashboard\\ActionDefinition::create()
             ->setName('listVersionHistory')
             ->setTitle('Show versions')
             ->setIcon('random')
             ->applyToModel(false));

        \$action = new \\Nucleus\\Dashboard\\ActionDefinition();
        \$action->setName('showVersion')
                ->setTitle('Show version')
                ->setReturnType('object')
                ->setReturnModel(\$model);

        \$item = \\{$queryClassname}::create()->findPK({$callargs});
        return array(\$action, \$item->getOneVersion(\$Version));
    }
";
    }

    protected function renderAnnotations(array $annotations)
    {
        return " * " . implode("\n     * ", $annotations);
    }

    protected function getPrimaryKeyAsArgs($table = null, $ignore = array())
    {
        $table = $table ?: $this->getTable();
        $peerClassname = $this->getNewStubPeerBuilder($table)->getFullyQualifiedClassname();

        $funcargs = array();
        $callargs = array();
        foreach ($table->getPrimaryKey() as $pk) {
            if (!in_array($pk, $ignore)) {
                $funcargs[] = '$' . $pk->getPhpName();
            }
            if ($pk->isEnumType()) {
                $callargs[] = sprintf('\\%s::getSqlValueForEnum(\\%s::%s, $%s)',
                    $peerClassname, $peerClassname, strtoupper($pk->getName()), $pk->getPhpName());
            } else {
                $callargs[] = '$' . $pk->getPhpName();
            }
        }

        $funcargs = implode(', ', $funcargs);

        if (count($callargs) > 1) {
            $callargs = 'array(' . implode(', ', $callargs) . ')';
        } else {
            $callargs = $callargs[0];
        }

        return array($funcargs, $callargs);
    }

    protected function addClassClose(&$script)
    {
        $script .= "
}";
    }
}