<?php

namespace Nucleus\Dashboard\Bridge\Propel;

use Nucleus\Dashboard\ActionBehaviors\AbstractActionBehavior;
use Nucleus\Dashboard\DashboardException;

class SortableActionBehavior extends AbstractActionBehavior
{
    public function getName()
    {
        return 'sortable';
    }

    public function invoke($data, $request, $response)
    {
        $model = $this->action->getReturnModel();
        if (!($obj = $model->loadObject($data))) {
            throw new DashboardException("Unknown model");
        }

        if (isset($data['delta'])) {
            $rank = $obj->getRank() + $data['delta'];
        } else if (isset($data['rank'])) {
            $rank = $data['rank'];
        }

        $obj->moveToRank($rank);
        $obj->save();
    }
}