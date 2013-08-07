<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Twig;

use Nucleus\IService\AssetManager\IAssetManager;
use Twig_Extension;
use Twig_Template;

/**
 * Description of AssetBagTwigExtension
 *
 * @author Martin
 * 
 * @\Nucleus\IService\DependencyInjection\Tag(name="twigRenderer.twigExtension")
 */
class AssetBagTwigExtension extends Twig_Extension
{
    /**
     * @var AssetBag 
     */
    private $assetBag;
    private $templateRenderingDepth = 0;

    /**
     *
     * @var Manager
     */
    private $assetManager;

    public function __construct()
    {
        $this->assetBag = new AssetBag();
    }

    /**
     * 
     * @param Manager $assetManager
     * 
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function initialize(IAssetManager $assetManager)
    {
        $this->assetManager = $assetManager;
    }

    public function getGlobals()
    {
        return array(
            "assetBag" => $this->assetBag
        );
    }

    public function preRenderTemplate(Twig_Template $template)
    {
        $this->templateRenderingDepth++;
    }

    public function postRenderTemplate(Twig_Template $template, $result)
    {
        $this->templateRenderingDepth--;
        if ($this->templateRenderingDepth == 0) {
            $result = str_replace(
                (string) $this->assetBag,implode("\n",$this->assetManager->getHtmTags($this->assetBag->clear())), $result
            );
        }
        return $result;
    }

    public function getName()
    {
        return 'asset_bag';
    }
}
