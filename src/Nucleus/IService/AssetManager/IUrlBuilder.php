<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\AssetManager;

/**
 *
 * @author Martin
 */
interface IUrlBuilder
{

    /**
     * Will build the complete Url, usefull when you need a cdn or complexe
     * processing base on url. This is use whitin the AssetManager
     * 
     * @param string $path
     */
    public function getUrl($path);
}
