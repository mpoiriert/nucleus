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
interface IFilePersister
{
    public function persist($filePath, $content);

    public function recover($path);

    public function exists($path);
}
