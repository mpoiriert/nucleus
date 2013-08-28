<?php

namespace Nucleus\IService\Dashboard;

/**
 * @Annotation
 */
class Sortable
{
    /**
     * Name of the action parameter to specify which field to sort
     */
    public $param = "sort";

    /**
     * Name of the action parameter to specify in which order to sort
     */
    public $order_param;
}
