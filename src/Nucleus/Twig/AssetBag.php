<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Twig;

/**
 * Description of AssetBag
 *
 * @author Martin
 */
class AssetBag
{
    private $assets = array();

    /**
     * Add a file to the asset bag. If the asset is already there
     * it will be ignored.
     * 
     * @param string $filePath
     */
    public function add($filePath)
    {
        if(!in_array($filePath,$this->assets)) {
            $this->assets[] = $filePath;
        }
    }

    public function clear()
    {
        $assets = $this->assets;
        $this->assets = array();
        return $assets;
    }

    public function __toString()
    {
        return '====ASSETS====';
    }
}
