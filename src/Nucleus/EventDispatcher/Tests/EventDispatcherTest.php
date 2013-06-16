<?php

namespace Nucleus\EventDispatcher\Tests;

use Nucleus\IService\EventDispatcher\Tests\EventDispatcherServiceTest;
use Nucleus\EventDispatcher\EventDispatcher;

class EventDispatcherTest extends EventDispatcherServiceTest
{

    protected function getEventDispatcherService()
    {
        return EventDispatcher::factory();
    }
}