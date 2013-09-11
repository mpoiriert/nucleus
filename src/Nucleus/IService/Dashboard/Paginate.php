<?php

namespace Nucleus\IService\Dashboard;

/**
 * @Annotation
 */
class Paginate
{
    /**
     * Number of items per page
     */
    public $per_page = 20;

    /**
     * Name of the action parameter used to specify the offset
     */
    public $offset_param;

    /**
     * Whether to automatically manage pagnination
     * Actions must return an Iterator
     */
    public $auto = false;
}
