<?php

namespace Nucleus\Dashboard\ActionBehaviors;

use Iterator;
use ArrayIterator;
use Countable;
use LimitIterator;
use Nucleus\Dashboard\DashboardException;

class PaginatedBehavior extends AbstractActionBehavior
{
    protected $params = array(
        'per_page' => 20,
        'offset_param' => null,
        'auto' => false
    );

    public function getName()
    {
        return 'paginated';
    }

    public function beforeInvoke($model, $data, &$params, $request, $response)
    {
        if (!$this->getParam('auto') && ($param = $this->getParam('offset_param')) !== null) {
            if (isset($data['__offset'])) {
                $params[$param] = $data['__offset'];
            } else {
                $params[$param] = 0;
            }
        }
    }

    public function afterInvoke($model, &$result, $request, $response)
    {
        $count = null;
        if ($this->getParam('auto') && $result !== null) {
            if (!($result instanceof Iterator) && !is_array($result)) {
                throw new DashboardException("List results expect an array or an Iterator, '" . get_class($result) . "' given");
            }
            list($count, $result) = $this->autoPaginateResults($request, $result);
        } else if ($result !== null) {
            $count = $result[0];
            $result = $result[1];
        }

        $this->count = $count;
    }

    public function formatInvokedResponse(&$data)
    {
        $data = array('count' => $this->count, 'data' => $data, 'per_page' => $this->getParam('per_page'));
    }

    protected function autoPaginateResults($request, $result)
    {
        $count = null;
        if (is_array($result)) {
            $result = new ArrayIterator($result);
        }
        if ($result instanceof Countable) {
            $count = $result->count();
        }
        $result = new LimitIterator($result, $request->query->get('__offset', 0), $request->query->get('__limit', 1));
        return array($count, $result);
    }
}