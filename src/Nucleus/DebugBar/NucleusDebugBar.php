<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\DebugBar;

use DebugBar\StandardDebugBar;
use DebugBar\DataCollector\DataCollectorInterface;
use Nucleus\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use Nucleus\IService\DependencyInjection\IServiceContainer;

/**
 * Description of NucleusDebugBar
 *
 * @author Martin
 */
class NucleusDebugBar extends StandardDebugBar
{
    protected $serviceContainer;

    protected $router;

    /**
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function initialize(IServiceContainer $serviceContainer, Router $routing)
    {
        $this->serviceContainer = $serviceContainer;
        $this->router = $routing;
    }

    /**
     * @param DataCollectorInterface[] $dataCollectors
     * 
     * @\Nucleus\IService\DependencyInjection\Inject(dataCollectors="@debugBar.dataCollector")
     */
    public function setDataCollectors(array $dataCollectors)
    {
        foreach($dataCollectors as $dataCollector) {
            $this->addCollector($dataCollector);
        }
    }

    /**
     * @\Nucleus\IService\EventDispatcher\Listen(eventName="Response.beforeSend")
     */
    public function prepareResponse(Response $response)
    {
        $request = $this->router->getCurrentRequest();
        if ($request->isXmlHttpRequest()) {
            if ($this->serviceContainer->has('debugBarOpenHandler')) {
                $this->getData();
                $response->headers->set('phpdebugbar-id', $this->getCurrentRequestId());
            } else {
                $headers = $this->getDataAsHeaders();
                foreach ($headers as $k => $v) {
                    $response->headers->set($k, $v);
                }
            }
        }
    }
}
