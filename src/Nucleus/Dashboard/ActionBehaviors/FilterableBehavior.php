<?php

namespace Nucleus\Dashboard\ActionBehaviors;

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
                if ($field->isQueryable()) {
                    $filters[$field->getProperty()] = $v;
                }
            }
        }

        $params[$this->getParam('param')] = $filters;
    }
}