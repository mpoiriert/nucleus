<?php

namespace Nucleus\Dashboard\Bridge\Propel;

use Behavior;

class DashboardModelBehavior extends Behavior
{
    protected $name = 'dashboard_model';
    
    protected $parameters = array(
        'include' => '',
        'exclude' => '',
        'delete_action' => 'true',
        'namealiases' => '',
        'htmlfields' => '',
        'repr' => 'id',
        'nolist' => '',
        'listable' => '',
        'noedit' => '',
        'editable' => '',
        'noview' => '',
        'viewable' => '',
        'noquery' => '',
        'queryable' => '',
        'children' => '',
        'noaddchildren' => '',
        'nocreatechildren' => '',
        'noremovechildren' => '',
        'noeditchildren' => '',
        'noviewchildren' => '',
        'childaliases' => '',
        'internal' => '',
        'propertylaliases' => '',
        'fkembed' => '',
        'novcmebed' => '',
        'typeoverrides' => '',
        'hide_rank_column' => 'true'
    );

    public function objectAttributes()
    {
        return "private static \$dashboardModelDefinition;\n";
    }

    public function objectMethods($builder)
    {
        $builder->declareClass('Nucleus\Dashboard\ModelDefinition');
        $builder->declareClass('Nucleus\Dashboard\FieldDefinition');
        $builder->declareClass('Nucleus\Dashboard\ActionDefinition');
        return $this->addGetModelDefinitionMethod($builder);
    }

    public function getListParameter($name)
    {
        return array_filter(array_map('trim', explode(',', $this->getParameter($name))));
    }

    public function getChildrenFKs()
    {
        $fks = array();
        $referrers = $this->getTable()->getReferrers();
        foreach ($this->getListParameter('children') as $child) {
            foreach ($referrers as $ref) {
                if ($ref->getTableName() == $child) {
                    $fks[] = $ref;
                    break;
                }
            }
        }
        return $fks;
    }

    public function getParentTable()
    {
        $table = $this->getTable();
        if ($b = $table->getBehavior('concrete_inheritance')) {
            return $table->getDatabase()->getTable($b->getParameter('extends'));
        }
        return null;
    }

    protected function addGetModelDefinitionMethod($builder)
    {
        $table = $this->getTable();

        $script = "public static function getDashboardModelDefinition() {\n"
                . "if (self::\$dashboardModelDefinition !== null) {\nreturn self::\$dashboardModelDefinition;\n}\n";

        if ($parent = $this->getParentTable()) {
            $parentClass = $parent->getNamespace() . '\\' . $parent->getPhpName();
            $script .= "self::\$dashboardModelDefinition = \$model = clone \\{$parentClass}::getDashboardModelDefinition();\n\n";
        } else {
            $script .= "self::\$dashboardModelDefinition = \$model = ModelDefinition::create();\n\n";
        }

        $script .= $this->addModelAttributes($builder)
                 . $this->addModelFields()
                 . $this->addModelActions();

        $script .= "return \$model;\n}";
        return $script;
    }

    protected function addModelAttributes($builder)
    {
        return "\$model->setClassName('" . $builder->getStubObjectBuilder()->getFullyQualifiedClassname() . "')\n"
             . "->setName('" . $this->getTable()->getPhpName() . "')\n"
             . "->setLoader(function(\$pk) { return \\" . $builder->getStubQueryBuilder()->getFullyQualifiedClassname() . "::create()->findPK(\$pk); })\n"
             . "->setValidationMethod(ModelDefinition::VALIDATE_WITH_METHOD);\n\n";
    }

    protected function addModelFields()
    {
        $script = '';
        $table = $this->getTable();

        $indexColumns = array();
        foreach (array_merge($table->getIndices(), $table->getUnices()) as $index) {
            foreach ($index->getColumns() as $c) {
                $indexColumns[] = $c;
            }
        }

        $indexColumns = array_unique($indexColumns);
        $includedColumns = $this->getIncludedColumns();
        $excludedColumns = $this->getExcludedColumns();

        foreach ($table->getColumns() as $column) {
            if (!empty($includedColumns) && !in_array($column->getName(), $includedColumns)) {
                continue;
            }
            if (in_array($column->getName(), $excludedColumns)) {
                continue;
            }
            $isIndexed = $column->isPrimaryKey() || in_array($column->getName(), $indexColumns);
            $script .= "\$model->addField(" . $this->addFieldDefinition($column, $isIndexed) . ");\n\n";
        }

        foreach ($this->getChildrenFKs() as $fk) {
            $script .= "\$model->addField(" . $this->addChildFieldDefinition($fk) . ");\n\n";
        }

        return $script;
    }

    protected function getIncludedColumns()
    {
        return $this->getListParameter('include');
    }

    protected function getExcludedColumns()
    {
        $cols = $this->getListParameter('exclude');
        $table = $this->getTable();

        if ($parent = $this->getParentTable()) {
            foreach ($parent->getColumns() as $column) {
                $cols[] = $column->getName();
            }
        } else if ($table->hasBehavior('concrete_inheritance_parent')) {
            $cols[] = $table->getBehavior('concrete_inheritance_parent')->getParameter('descendant_column');
        }

        if ($table->hasBehavior('sortable') && $this->getParameter('hide_rank_column') == 'true') {
            $cols[] = $table->getBehavior('sortable')->getParameter('rank_column');
        }

        if ($table->hasBehavior('versionable')) {
            $cols[] = $table->getBehavior('versionable')->getParameter('version_column');
        }

        return $cols;
    }

    protected function addModelActions()
    {
        $script = '';
        $table = $this->getTable();

        if (!$table->hasBehavior('concrete_inheritance_parent') && $this->getParameter('delete_action') == 'true') {
            $script = "\$model->addAction(ActionDefinition::create()\n"
                    . "->setName('delete')\n"
                    . "->setTitle('Delete')\n"
                    . "->setIcon('trash')\n"
                    . "->addBehavior(new \Nucleus\Dashboard\ActionBehaviors\ConfirmBehavior()));\n\n";
        }

        return $script;
    }

    protected function addFieldDefinition($column, $isIndexed = false)
    {
        $isIdentifier = $column->isPrimaryKey();
        $name = $column->getName();
        $fk = null;

        if ($column->isForeignKey() && !$column->hasMultipleFK()) {
            $fks = $column->getForeignKeys();
            $fk = $fks[0];
            $name = $fk->getPhpName() ?: $fk->getForeignTable()->getPhpName();
        }

        $name = ucfirst(str_replace('_', ' ', $this->getAlias($name, 'namealiases')));

        if (!($type = $column->getPhpType())) {
            $type = 'string';
            if ($column->getType() == 'OBJECT') {
                $type = 'object';
            }
        }

        $script = "FieldDefinition::create()\n"
                . "->setProperty('" . $this->getAlias($column->getPhpName(), 'propertyaliases') . "')\n"
                . "->setInternalProperty('" . $column->getPhpName() . "')\n"
                . "->setAccessMethod(FieldDefinition::ACCESS_GETTER_SETTER)\n"
                . "->setName('" . $name . "')\n"
                . "->setType('" . $this->getOverride($column->getName(), $type, 'typeoverrides') . "')\n"
                . "->setIdentifier(" . ($isIdentifier ? 'true' : 'false') . ")\n"
                . "->setOptional(" . ($column->isNotNull() ? 'false' : 'true') . ")\n"
                . "->setDescription('" . str_replace("'", "\\'", $column->getDescription()) . "')";


        $editable = !$isIdentifier || !$column->isAutoIncrement();
        if ($b = $this->getTable()->getBehavior('timestampable')) {
            $editable = $editable && !in_array($column->getName(), array(
                $b->getParameter('create_column'), $b->getParameter('update_column')));
        }

        if ($b = $this->getTable()->getBehavior('sortable')) {
            if ($column->getName() == $b->getParameter('rank_column')) {
                $script .= "\n->setGetterSetterMethodNames('getRank', 'moveToRank')";
            }
        }

        $visibility = array('list', 'view');
        if ($column->isLobType()) {
            $visibility = array();
        }
        if ($editable) {
            $visibility[] = 'edit';
        }
        if ($isIndexed) {
            $visibility[] = 'query';
        }
        $script .= "\n->setVisibility(array('" . implode("', '", $this->getVisibility($column->getName(), $visibility)) . "'))";

        if (($defaultValue = $column->getPhpDefaultValue()) !== null) {
            if (is_string($defaultValue)) {
                $defaultValue = "'" . str_replace("'", "\\'", $defaultValue) . "'";
            } else if (is_bool($defaultValue)) {
                $defaultValue = $defaultValue ? 'true' : 'false';
            }
            $script .= "\n->setDefaultValue(" . $defaultValue . ")";
        }

        if ($fk) {
            $fkname = $fk->getPhpName() ?: $fk->getForeignTable()->getPhpName();
            $table = $fk->getForeignTable();
            if (!($b = $table->getBehavior('dashboard_controller'))) {
                $b = $table->getBehavior('dashboard_parent_controller');
            }
            if (count($fk->getForeignColumns()) == 1 && $b) {
                $fcols = $fk->getForeignColumnObjects();
                $fcol = $fcols[0];
                $fqdn = $table->getNamespace() . '\\' . $table->getPhpName();
                $embedRelated = in_array($column->getName(), $this->getListParameter('fkembed'));
                $embedVC = !in_array($column->getName(), $this->getListParameter('novcembed'));
                $script .= "\n->setRelatedModel(\\$fqdn::getDashboardModelDefinition(), 'get{$fkname}', null, null, " . ($embedRelated ? 'true' : 'false') . ")"
                         . "\n->setValueController('" . $table->getPhpName() . "DashboardController', '" . $fcol->getPhpName() . "', null, " . ($embedVC ? 'true' : 'false') . ")";
            }
        }

        if ($this->getParameter('repr') == $column->getName()) {
            $script .= "\n->setStringRepr(true)";
        }

        if (in_array($column->getName(), $this->getListParameter('internal'))) {
            $script .= "\n->setInternal(true)";
        }

        $options = null;
        if (!($type = $this->getOverride($column->getName(), null, 'htmlfields'))) {
            if ($column->isTemporalType()) {
                if ($column->getType() == 'DATE') {
                    $type = 'datepicker';
                    $options['dateFormat'] = 'mm/dd/yy';
                } else if ($column->getType() == 'TIME') {
                    $type = 'timepicker';
                    $options['timeFormat'] = 'HH:mm:ss';
                } else if ($column->getType() == 'TIMESTAMP') {
                    $type = 'datetimepicker';
                    $options = array('dateFormat' => 'yy-mm-dd', 'timeFormat' => 'HH:mm:ss');
                }
            } else if ($column->isLobType()) {
                $type = 'file';
            } else if ($column->isEnumType()) {
                $type = 'select';
                $options = array('values' => $column->getValueSet());
            }
        }

        if ($type) {
            $script .= "\n->setFormFieldType('$type', " . var_export($options, true) . ")";
        }

        return $script;
    }

    protected function addChildFieldDefinition($fk)
    {
        $table = $fk->getTable();
        $fqdn = $table->getNamespace() . '\\' . $table->getPhpName();
        $controllerFqdn = $table->getPhpName() . 'DashboardController';

        $availableActions = array('edit', 'create', 'remove', 'view');
        if (!$table->getIsCrossRef()) {
            $availableActions[] = 'add';
        }
        
        $actions = array();
        foreach ($availableActions as $a) {
            if (!in_array($table->getName(), $this->getListParameter("no{$a}children"))) {
                $actions[] = $a;
            }
        }

        $lcols = $fk->getLocalColumnObjects();
        $lcol = $lcols[0];
        $fcols = $fk->getForeignColumnObjects();
        $fcol = $fcols[0];

        $script = "FieldDefinition::create()\n"
                . "->setProperty('" . $table->getPhpName() . "s')\n"
                . "->setAccessMethod(FieldDefinition::ACCESS_GETTER_SETTER)\n"
                . "->setName('" . $this->getAlias($table->getName(), 'childaliases') . "')\n"
                . "->setType('object[]')\n"
                . "->setVisibility(array('view', 'edit'))\n"
                . "->setRelatedModel(\\$fqdn::getDashboardModelDefinition(), 'get" . $table->getPhpName() . "s', '$controllerFqdn', array('" . implode("', '", $actions) . "'))\n"
                . "->setValueController('" . $this->getTable()->getPhpName() . "DashboardController', '" . $lcol->getPhpName() . "', '" . $fcol->getPhpName() . "')";

        return $script;
    }

    protected function getAlias($name, $param)
    {
        return $this->getOverride($name, $name, $param);
    }

    protected function getOverride($name, $default, $param)
    {
        $overrides = array_filter(explode(',', $this->getParameter($param)));
        foreach ($overrides as $override) {
            list($c, $o) = explode(':', $override, 2);
            if ($c == $name) {
                return $o;
            }
        }
        return $default;
    }

    protected function getVisibility($name, $default)
    {
        $visibility = array();
        foreach (array('list', 'edit', 'view', 'query') as $v) {
            if (!in_array($v, $default) && in_array($name, $this->getListParameter("{$v}able"))) {
                $visibility[] = $v;
            } else if (in_array($v, $default) && !in_array($name, $this->getListParameter("no$v"))) {
                $visibility[] = $v;
            }
        }
        return $visibility;
    }
}