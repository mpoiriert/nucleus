<?php

namespace Nucleus\IService\Dashboard;

/**
 * @Annotation
 */
class Orderable
{
    /**
     * Name of the action parameter to specify which field to order
     */
    public $param = "order_by";

    /**
     * Name of the action parameter to specify in which order to sort
     */
    public $order_param;
}
