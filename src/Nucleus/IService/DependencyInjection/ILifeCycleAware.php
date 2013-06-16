<?php

namespace Nucleus\IService\DependencyInjection;

/**
 * This interface tel the Service Container to call start and shutdown at
 * the proper moment. This allow the Service to know that all the injection
 * and other initialization have been done prior to the start call and the
 * shutdown method will be done prior to the __destruct method done by the
 * PHP engine in the container context;
 * 
 * @author Martin Poirier Theoret <mpoiriert@gmail.com>
 */
interface ILifeCycleAware
{

    public function serviceShutdown();

    public function serviceStart();
}
