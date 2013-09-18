<?php

namespace Nucleus\IService\Routing;

use Exception;

class NoHostFoundForCultureException extends Exception
{
    static public function formatMessage($culture)
    {
        return 'No host found for [' . $culture . ']';
    }
}
