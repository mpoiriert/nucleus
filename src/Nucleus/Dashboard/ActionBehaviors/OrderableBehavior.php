<?php

namespace Nucleus\Dashboard\ActionBehaviors;

class OrderableBehavior extends AbstractActionBehavior
{
    protected $params = array(
        'param' => 'order_by',
        'order_param' => null
    );

    public function getName()
    {
        return 'orderable';
    }

    public function beforeInvoke($model, $data, &$params, $request, $response)
    {
        if (isset($data['__sort'])) {
            $params[$this->getParam('param')] = $data['__sort'];
            if (isset($data['__sort_order'])) {
                if (($param = $this->getParam('order_param')) !== null) {
                    if (!in_array(strtolower($data['__sort_order']), array('asc', 'desc'))) {
                        $data['__sort_order'] = 'asc';
                    }
                    $params[$param] = strtoupper($data['__sort_order']);
                }
            }
        }
    }
}