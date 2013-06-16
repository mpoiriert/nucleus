<?php

namespace Nucleus\IService\Cache;

/**
 * Interface for categories
 */
interface ICacheCategory
{
    /**
     * Name of the default mandatory category
     * @var string
     */
    const NAME_DEFAULT = 'default';

    /**
     * Name of the default mandatory category
     * @var string
     */
    const NAME_SYSTEM = 'system';

    /**
     * @return string
     */
    public function getName();

    public function getVersion();

    public function getVersionCreationTimestamp();

    /**
     * Clear all the entry of the current cache category
     */
    public function clear();
}