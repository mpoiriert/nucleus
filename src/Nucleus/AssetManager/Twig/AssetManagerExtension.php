<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\AssetManager\Twig;

use Twig_Extension;
use Nucleus\AssetManager\Manager;

/**
 * Description of TwigExtension
 *
 * @author Martin
 * 
 * @\Nucleus\IService\DependencyInjection\Tag(name="twigRenderer.twigExtension")
 */
class AssetManagerExtension extends Twig_Extension
{
    /**
     * @var \Nucleus\AssetManager\Manager
     */
    private $assetManager;

    /**
     * @param \Nucleus\AssetManager $assetManager
     * 
     * @Inject
     */
    public function setAssetManager(Manager $assetManager)
    {
        $this->assetManager = $assetManager;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'nucleus_asset', array($this, 'getHtmlTags'), array('is_safe' => array('html'))
            )
        );
    }

    public function getHtmlTags()
    {
        $files = func_get_args();
        return implode("\n", $this->assetManager->getHtmTags($files));
    }

    public function getName()
    {
        return 'nucleusAssetManager';
    }
}
