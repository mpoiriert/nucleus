<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Twig\Tests;

use Nucleus\IService\AssetManager\IAssetManager;
use Nucleus\Twig\AssetBagTwigExtension;
use Twig_Test_IntegrationTestCase;

/**
 * Description of TwigTemplateTest
 *
 * @author Martin
 */
class TwigTemplateTest extends Twig_Test_IntegrationTestCase
{

    protected function getExtensions()
    {
        $extension = new AssetBagTwigExtension();
        $extension->initialize(new AssetManager());
        return array($extension);
    }

    protected function getFixturesDir()
    {
        return __DIR__ . '/fixtures/assets';
    }
}

class AssetManager implements IAssetManager
{

    public function getHtmTags(array $files)
    {
        return $files;
    }
}
