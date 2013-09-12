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
        'noquery' => ''
    );

    public function objectAttributes()
    {
        return "private static \$dashboardModelDefinition;\n";
    }

    public function objectMethods($builder)
    {
        return $this->addGetModelDefinitionMethod($builder);
    }

    public function addGetModelDefinitionMethod($builder)
    {
        $table = $this->getTable();
        $script = "public static function getDashboardModelDefinition() {\n"
                . "if (self::\$dashboardModelDefinition !== null) {\nreturn self::\$dashboardModelDefinition;\n}\n"
                . "\$model = \\Nucleus\\Dashboard\\ModelDefinition::create()\n"
                . "->setClassName('" . $builder->getStubObjectBuilder()->getFullyQualifiedClassname() . "')\n"
                . "->setLoader(function(\$pk) { return \\" . $builder->getStubQueryBuilder()->getFullyQualifiedClassname() . "::create()->findPK(\$pk); })\n"
                . "->setValidationMethod(\\Nucleus\\Dashboard\\ModelDefinition::VALIDATE_WITH_METHOD);\n"
                . "self::\$dashboardModelDefinition = \$model;\n\n";

        if (($includedFields = $this->getParameter('include')) !== null) {
            $includedFields = explode(',', $includedFields);
        } else if (($excludedFields = $this->getParameter('exclude')) !== null) {
            $excludedFields = explode(',', $excludedFields);
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

        foreach ($table->getColumns() as $column) {
            if ($includedFields !== null && !in_array($column->getName(), $includedFields)) {
                continue;
            } else if ($excludedFields !== null && in_array($column->getName(), $excludedFields)) {
                continue;
            }
            $queryable = $column->isPrimaryKey() || in_array($column->getName(), $indexColumns);
            $script .= "\$model->addField(" . $this->addFieldDefinition($column, $queryable) . ");\n\n";
        }

        if ($table->hasBehavior('i18n_dictionary')) {
            $entries = explode(',', $table->getBehavior('i18n_dictionary')->getParameter('entries'));
            foreach ($entries as $name) {
                if ($includedFields !== null && !in_array($name, $includedFields)) {
                    continue;
                } else if ($excludedFields !== null && in_array($name, $excludedFields)) {
                    continue;
                }
                $script .= "\$model->addField(" . $this->addI18nFieldDefinition(trim($name)) . ");\n\n";
            }
        }

        if ($this->getParameter('delete_action') == 'true') {
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

        if (!$isIdentifier || !$column->isAutoIncrement()) {
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
            if (!$table->hasCompositePrimaryKey() && $table->hasBehavior('dashboard_controller')) {
                $pks = $table->getPrimaryKey();
                $pk = $pks[0];
                $fqdn = $table->getNamespace() . '\\' . $table->getPhpName();
                $script .= "\n->setRelatedModel(\\$fqdn::getDashboardModelDefinition())"
                         . "\n->setValueController('" . $table->getPhpName() . "DashboardController', '" . $pk->getPhpName() . "')";
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
            if (in_array($v, $default) && !in_array($name, explode(',', $this->getParameter("no$v")))) {
                $visibility[] = $v;
            }
        }
        return $visibility;
    }
}