<?php

namespace Nucleus\Dashboard\ActionBehaviors;

use Nucleus\Dashboard\FieldDefinition;

class FilterableBehavior extends AbstractActionBehavior
{
    protected $params = array(
        'param' => 'filters',
        'operator_param' => null
    );

    public function getName()
    {
        return 'filterable';
    }

    public function beforeInvoke($model, $data, &$params, $request, $response)
    {
        if (!isset($data['__filters'])) {
            return;
        }

        $model = $this->action->getReturnModel();
        $filters = array();
        $rawFilters = json_decode($data['__filters'], true);
        foreach ($rawFilters as $k => $v) {
            if (($field = $model->getFieldByProperty($k)) !== null) {
                if ($field->isVisible(FieldDefinition::VISIBILITY_QUERY)) {
                    $filters[$field->getProperty()] = $v;
                }
            }
        }

        $params[$this->getParam('param')] = $filters;
    }
}