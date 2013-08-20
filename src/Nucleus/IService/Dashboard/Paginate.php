<?php

namespace Nucleus\IService\Dashboard;

/**
 * @Annotation
 */
class Paginate
{
    public $per_page = 20;
    public $offset_param;
    public $auto = false;
}
