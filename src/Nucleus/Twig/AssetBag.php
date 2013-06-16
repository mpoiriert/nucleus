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

    public function add($filePath)
    {
        $this->assets[] = $filePath;
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
