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
interface IAssetManager
{
    const NUCLEUS_SERVICE_NAME = "assetManager";

    public function getHtmTags(array $files);
}
