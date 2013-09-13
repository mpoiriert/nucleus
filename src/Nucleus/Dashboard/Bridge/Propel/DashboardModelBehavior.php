<?php

namespace Nucleus\Dashboard\Bridge\Propel;

use Behavior;

class DashboardModelBehavior extends Behavior
{
    protected $name = 'dashboard_model';
    
    protected $parameters = array(
        'include' => null,
        'exclude' => null,
        'delete_action' => 'true',
        'aliases' => '',
        'htmlfields' => '',
        'repr' => 'id',
        'nolist' => '',
        'noedit' => '',
        'noview' => '',
        'noquery' => '',
        'children' => ''
    );

    public function objectAttributes()
    {
        return "private static \$dashboardModelDefinition;\n";
    }

    public function objectMethods($builder)
    {
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

    public function addGetModelDefinitionMethod($builder)
    {
        $table = $this->getTable();

        $excludedFields = array();
        if ($this->getParameter('include') !== null) {
            $includedFields = $this->getListParameter('include');
        } else if ($this->getParameter('exclude') !== null) {
            $excludedFields = $this->getListParameter('exclude');
        } else {
            $excludedFields = array();
        }

        if ($inherited = $table->hasBehavior('concrete_inheritance')) {
            $parent = $table->getDatabase()->getTable($table->getBehavior('concrete_inheritance')->getParameter('extends'));
            foreach ($parent->getColumns() as $column) {
                $excludedFields[] = $column->getName();
            }
            if ($parent->hasBehavior('i18n_dictionary')) {
                $excludedFields = array_merge($excludedFields, 
                    array_map('trim', explode(',', $parent->getBehavior('i18n_dictionary')->getParameter('entries'))));
            }
        } else if ($isParent = $table->hasBehavior('concrete_inheritance_parent')) {
            $excludedFields[] = $table->getBehavior('concrete_inheritance_parent')->getParameter('descendant_column');
        }

        $indexColumns = array();
        foreach ($table->getIndices() as $index) {
            foreach ($index->getColumns() as $c) {
                $indexColumns[] = $c;
            }
        }
        $indexColumns = array_unique($indexColumns);

        if ($table->hasBehavior('sortable')) {
            $excludedFields[] = $table->getBehavior('sortable')->getParameter('rank_column');
        }

        $script = "public static function getDashboardModelDefinition() {\n"
                . "if (self::\$dashboardModelDefinition !== null) {\nreturn self::\$dashboardModelDefinition;\n}\n";

        if ($inherited) {
            $parentClass = $parent->getNamespace() . '\\' . $parent->getPhpName();
            $script .= "self::\$dashboardModelDefinition = \$model = clone \\{$parentClass}::getDashboardModelDefinition();\n\n";
        } else {
            $script .= "self::\$dashboardModelDefinition = \$model = \\Nucleus\\Dashboard\\ModelDefinition::create();\n\n";
        }

        $script .= "\$model->setClassName('" . $builder->getStubObjectBuilder()->getFullyQualifiedClassname() . "')\n"
                 . "->setLoader(function(\$pk) { return \\" . $builder->getStubQueryBuilder()->getFullyQualifiedClassname() . "::create()->findPK(\$pk); })\n"
                 . "->setValidationMethod(\\Nucleus\\Dashboard\\ModelDefinition::VALIDATE_WITH_METHOD);\n\n";

        foreach ($table->getColumns() as $column) {
            if ($includedFields !== null && !in_array($column->getName(), $includedFields)) {
                continue;
            }
            if (in_array($column->getName(), $excludedFields)) {
                continue;
            }
            $queryable = $column->isPrimaryKey() || in_array($column->getName(), $indexColumns);
            $script .= "\$model->addField(" . $this->addFieldDefinition($column, $queryable) . ");\n\n";
        }

        if ($table->hasBehavior('i18n_dictionary')) {
            $entries = array_map('trim', explode(',', $table->getBehavior('i18n_dictionary')->getParameter('entries')));
            foreach ($entries as $name) {
                if ($includedFields !== null && !in_array($name, $includedFields)) {
                    continue;
                }
                if (in_array($name, $excludedFields)) {
                    continue;
                }
                $script .= "\$model->addField(" . $this->addI18nFieldDefinition($name) . ");\n\n";
            }
        }

        foreach ($this->getChildrenFKs() as $fk) {
            $script .= "\$model->addField(" . $this->addChildFieldDefinition($fk) . ");\n\n";
        }

        if (!$isParent && $this->getParameter('delete_action') == 'true') {
            $script .= "\$model->addAction(\\Nucleus\\Dashboard\\ActionDefinition::create()\n"
                     . "->setName('delete')\n"
                     . "->setTitle('Delete')\n"
                     . "->setIcon('trash'));\n\n";
        }

        $script .= "return \$model;\n}";
        return $script;
    }

    public function addFieldDefinition($column, $queryable = false)
    {
        $isIdentifier = $column->isPrimaryKey();
        $visibility = array('list', 'view');

        $name = ucfirst(str_replace('_', ' ', $this->getAlias($column->getName())));

        $script = "\\Nucleus\\Dashboard\\FieldDefinition::create()\n"
                . "->setProperty('" . $column->getPhpName() . "')\n"
                . "->setAccessMethod(\\Nucleus\\Dashboard\\FieldDefinition::ACCESS_GETTER_SETTER)\n"
                . "->setName('" . $name . "')\n"
                . "->setType('" . $column->getPhpType() . "')\n"
                . "->setIdentifier(" . ($isIdentifier ? 'true' : 'false') . ")\n"
                . "->setOptional(" . ($column->isNotNull() ? 'false' : 'true') . ")\n"
                . "->setDescription('" . str_replace("'", "\\'", $column->getDescription()) . "')";

        $editable = !$isIdentifier || !$column->isAutoIncrement();
        if ($this->getTable()->hasBehavior('timestampable')) {
            $editable = $editable && !in_array($column->getName(), array(
                $this->getTable()->getBehavior('timestampable')->getParameter('create_column'),
                $this->getTable()->getBehavior('timestampable')->getParameter('update_column')));
        }

        if ($editable) {
            $visibility[] = 'edit';
        }
        if (!$queryable) {
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

        if ($column->isForeignKey() && !$column->hasMultipleFK()) {
            $fks = $column->getForeignKeys();
            $fk = $fks[0];
            $table = $fk->getForeignTable();
            if (count($fk->getForeignColumns()) == 1 && $table->hasBehavior('dashboard_controller')) {
                $fcols = $fk->getForeignColumnObjects();
                $fcol = $fcols[0];
                $fqdn = $table->getNamespace() . '\\' . $table->getPhpName();
                $script .= "\n->setRelatedModel(\\$fqdn::getDashboardModelDefinition())"
                         . "\n->setValueController('" . $table->getPhpName() . "DashboardController', '" . $fcol->getPhpName() . "')";
            }
        }

        if ($this->getParameter('repr') == $column->getName()) {
            $script .= "\n->setStringRepr(true)";
        }

        if ($t = $this->getFormFieldType($column->getName())) {
            $script .= "\n->setFormFieldType('$t')";
        }

        return $script;
    }

    protected function addI18nFieldDefinition($name)
    {
        $script = "\\Nucleus\\Dashboard\\FieldDefinition::create()\n"
                . "->setProperty('" . ucfirst($name) . "')\n"
                . "->setAccessMethod(\\Nucleus\\Dashboard\\FieldDefinition::ACCESS_GETTER_SETTER)\n"
                . "->setName('" . $name . "')\n"
                . "->setType('string')\n"
                . "->setOptional(true)\n"
                . "->setVisibility(array('" . implode("', '", $this->getVisibility($name, array('list', 'view', 'edit'))) . "'))";

        if ($t = $this->getFormFieldType($name)) {
            $script .= "\n->setFormFieldType('$t')";
        }

        return $script;
    }

    protected function addChildFieldDefinition($fk)
    {
        $table = $fk->getTable();
        $fqdn = $table->getNamespace() . '\\' . $table->getPhpName();
        $controllerFqdn = $table->getPhpName() . 'DashboardController';

        $lcols = $fk->getLocalColumnObjects();
        $lcol = $lcols[0];
        $fcols = $fk->getForeignColumnObjects();
        $fcol = $fcols[0];

        $script = "\\Nucleus\\Dashboard\\FieldDefinition::create()\n"
                . "->setProperty('" . ucfirst($table->getName()) . "s')\n"
                . "->setAccessMethod(\\Nucleus\\Dashboard\\FieldDefinition::ACCESS_GETTER_SETTER)\n"
                . "->setName('" . $table->getName() . "s')\n"
                . "->setType('object[]')\n"
                . "->setVisibility(array('view', 'edit'))\n"
                . "->setRelatedModel(\\$fqdn::getDashboardModelDefinition(), '$controllerFqdn')\n"
                . "->setValueController('" . $this->getTable()->getPhpName() . "DashboardController', '" . $lcol->getPhpName() . "', '" . $fcol->getPhpName() . "')";

        return $script;
    }

    protected function getAlias($name)
    {
        $aliases = array_filter(explode(',', $this->getParameter('aliases')));
        foreach ($aliases as $alias) {
            list($c, $a) = explode(':', $alias, 2);
            if ($c == $name) {
                return $a;
            }
        }
        return $name;
    }

    protected function getFormFieldType($name)
    {
        $types = array_filter(explode(',', $this->getParameter('htmlfields')));
        foreach ($types as $type) {
            list($c, $a) = explode(':', $type, 2);
            if ($c == $name) {
                return $a;
            }
        }
    }

    protected function getVisibility($name, $default)
    {
        $visibility = array();
        foreach (array('list', 'edit', 'view', 'query') as $v) {
            if (in_array($v, $default) && !in_array($name, $this->getListParameter("no$v"))) {
                $visibility[] = $v;
            }
        }
        return $visibility;
    }
}