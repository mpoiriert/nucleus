<?php

namespace Nucleus\IService\Dashboard;

/**
 * @Annotation
 */
class Filterable
{
    /**
     * Name of the action parameter to specify which field to filter
     */
    public $param = "filters";
}
