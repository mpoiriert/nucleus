<?php

namespace Nucleus\Dashboard\Bridge\Propel;

use Behavior;

class DashboardModelBehavior extends Behavior
{
    protected $parameters = array(
        'include' => null,
        'exclude' => null
    );

    public function objectMethods($builder)
    {
        return $this->addGetModelDefinitionMethod($builder);
    }

    public function addGetModelDefinitionMethod($builder)
    {
        $table = $this->getTable();
        $script = "public static function getDashboardModelDefinition() {\n"
                . "\$model = \\Nucleus\\Dashboard\\ModelDefinition::create()\n"
                . "->setClassName('" . $builder->getObjectClassname() . "')\n"
                . "->setLoader(function(\$pk) { return " . $builder->getQueryClassname() . "::create()->findPK(\$pk); })\n"
                . "->setValidationMethod(\\Nucleus\\Dashboard\\ModelDefinition::VALIDATE_WITH_METHOD);\n\n";

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

        $script .= "\$model->addAction(\\Nucleus\\Dashboard\\ActionDefinition::create()\n"
                 . "->setName('delete')\n"
                 . "->setTitle('Delete')\n"
                 . "->setIcon('trash'));\n\n";

        $script .= "return \$model;\n}";
        return $script;
    }

    public function addFieldDefinition($column, $queryable = false)
    {
        $isIdentifier = $column->isPrimaryKey();

        $script = "\\Nucleus\\Dashboard\\FieldDefinition::create()\n"
                . "->setProperty('" . $column->getPhpName() . "')\n"
                . "->setAccessMethod(\\Nucleus\\Dashboard\\FieldDefinition::ACCESS_GETTER_SETTER)\n"
                . "->setName('" . $column->getName() . "')\n"
                . "->setIdentifier(" . ($isIdentifier ? 'true' : 'false') . ")\n"
                . "->setType('" . $column->getPhpType() . "')\n"
                . "->setOptional(" . ($column->isNotNull() ? 'false' : 'true') . ")\n"
                . "->setQueryable(" . ($queryable ? 'true' : 'false') . ")\n"
                . "->setDescription('" . str_replace("'", "\\'", $column->getDescription()) . "')";

        if ($isIdentifier) {
            $script .= "\n->setEditable(false)";
        }

        if (($defaultValue = $column->getPhpDefaultValue()) !== null) {
            if (is_string($defaultValue)) {
                $defaultValue = "'" . str_replace("'", "\\'", $defaultValue) . "'";
            } else if (is_bool($defaultValue)) {
                $defaultValue = $defaultValue ? 'true' : 'false';
            }
            $script .= "\n->setDefaultValue(" . $defaultValue . ")";
        }

        return $script;
    }
}