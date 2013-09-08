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


        foreach ($this->getTable()->getColumns() as $column) {
            if ($includedFields !== null && !in_array($column->getName(), $includedFields)) {
                continue;
            } else if ($excludedFields !== null && in_array($column->getName(), $excludedFields)) {
                continue;
            }
            $script .= "\$model->addField(" . $this->addFieldDefinition($column) . ");\n\n";
        }

        $script .= "\$model->addAction(\\Nucleus\\Dashboard\\ActionDefinition::create()\n"
                 . "->setName('delete')\n"
                 . "->setTitle('Delete')\n"
                 . "->setIcon('trash'));\n\n";

        $script .= "return \$model;\n}";
        return $script;
    }

    public function addFieldDefinition($column)
    {
        $isIdentifier = $column->isPrimaryKey();
        $script = "\\Nucleus\\Dashboard\\FieldDefinition::create()\n"
                . "->setProperty('" . $column->getPhpName() . "')\n"
                . "->setAccessMethod(\\Nucleus\\Dashboard\\FieldDefinition::ACCESS_GETTER_SETTER)\n"
                . "->setName('" . $column->getName() . "')\n"
                . "->setIdentifier(" . ($isIdentifier ? 'true' : 'false') . ")\n"
                . "->setType('" . $column->getPhpType() . "')\n"
                . "->setOptional(" . ($column->isNotNull() ? 'false' : 'true') . ")\n"
                . "->setDescription('" . str_replace("'", "\\'", $column->getDescription()) . "')";

        if ($isIdentifier) {
            $script .= "\n->setEditable(false)";
        }

        return $script;
    }
}