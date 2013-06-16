<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\AssetManager;

use Nucleus\IService\AssetManager\IUrlBuilder;

/**
 * Description of UrlBuilder
 *
 * @author Martin
 */
class UrlBuilder implements IUrlBuilder
{

    public function getUrl($relativePath)
    {
        return $relativePath;
    }
}
