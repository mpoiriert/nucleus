<?php

namespace Nucleus\IService\Cache;

/**
 * The value was not found in the cache storage
 */
class EntryNotFoundException extends CacheException
{
    static public function formatMessage($name, $namespace)
    {
        return 'The entrye named [' . $name . '] in namespace [' . $namespace . '] cannot be found.';
    }
}