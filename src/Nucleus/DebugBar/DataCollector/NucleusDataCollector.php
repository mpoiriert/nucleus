<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\DebugBar\DataCollector;

use Nucleus\DependencyInjection\BaseAspect;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\DataCollectorInterface;
use Nucleus\DebugBar\IAssetProvider;
use Nucleus\IService\Routing\IRouterService;

/**
 * Description of CacheDataCollector
 *
 * @author Martin
 * 
 * @\Nucleus\IService\DependencyInjection\Tag(name="debugBar.dataCollector")
 */
class NucleusDataCollector extends BaseAspect implements Renderable, DataCollectorInterface, IAssetProvider
{
    public function collect()
    {
        return array();
    }

    public function getCssFiles()
    {
        return array();
    }

    public function getJsFiles()
    {
        return array();
    }

    public function getName()
    {
        return 'nucleus';
    }

    public function getWidgets()
    {
        return array(
            "nucleusDocumentation" => array(
                "indicator" => "Nucleus.DebugBar.LinkIndicator",
                "title" => "ApiDoc",
                "icon" => "book",
                "href" => $this->getRouter()->generate("apidoc"),
                "target" => "_blank"
            )
        );
    }
    
    /**
     * @return Nucleus\IService\Routing\IRouterService
     */
    private function getRouter()
    {
        return $this->getServiceContainer()->getServiceByName(IRouterService::NUCLEUS_SERVICE_NAME);
    }
}
