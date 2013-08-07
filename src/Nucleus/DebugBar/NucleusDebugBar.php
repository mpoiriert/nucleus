<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\DebugBar;

use DebugBar\StandardDebugBar;
use \DebugBar\DataCollector\DataCollectorInterface;

/**
 * Description of NucleusDebugBar
 *
 * @author Martin
 */
class NucleusDebugBar extends StandardDebugBar
{
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
}
