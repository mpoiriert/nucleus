<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\DebugBar;

/**
 *
 * @author Martin
 */
interface IAssetProvider
{
    public function getJsFiles();
    public function getCssFiles();
}
