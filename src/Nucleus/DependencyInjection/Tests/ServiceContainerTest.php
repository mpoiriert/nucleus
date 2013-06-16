<?php

namespace Nucleus\DependencyInjection\Tests;

use Nucleus\DependencyInjection\BaseServiceContainer;
use Nucleus\IService\DependencyInjection\Tests\ServiceContainerTest as BaseServiceContainerTest;

class ServiceContainerTest extends BaseServiceContainerTest
{

    protected function getServiceContainer($configuration)
    {
        return BaseServiceContainer::factory($configuration);
    }
}