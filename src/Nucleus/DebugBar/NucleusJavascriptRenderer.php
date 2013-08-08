<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\DebugBar;

use DebugBar\JavascriptRenderer;

/**
 * Description of NucleusJavascriptRenderer
 *
 * @author Martin
 */
class NucleusJavascriptRenderer extends JavascriptRenderer
{
    public function getAssetFiles($type = null)
    {
        $files = parent::getAssetFiles(null);
        
        $files[0][] = 'nucleus/style.css';
        $files[1][] = 'nucleus/NucleusDebugBar.js';
        
        foreach($this->debugBar->getCollectors() as $collector) {
            if($collector instanceof IAssetProvider) {
                $files[0] = array_merge($files[0],$collector->getCssFiles());
                $files[1] = array_merge($files[1],$collector->getJsFiles());
            }
        }
        
        return $this->filterAssetArray($files,$type);
    }
}
