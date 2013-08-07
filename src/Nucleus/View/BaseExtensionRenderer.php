<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\View;

use \Nucleus\IService\View\ITemplateRendererService;

/**
 * Description of BaseExtensionRenderer
 *
 * @author Martin
 */
abstract class BaseExtensionRenderer implements ITemplateRendererService
{
    private $extensions;

    /**
     * @var FileSystemLoader
     */
    private $fileLoader;

    /**
     * @param FileSystemLoader $loader
     * 
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function setFileLoader(FileSystemLoader $templateFileLoader)
    {
        $this->fileLoader = $templateFileLoader;
    }

    /**
     * @return FileSystemLoader
     */
    public function getFileSystemLoader()
    {
        return $this->fileLoader;
    }

    protected function setExtensions($extensions)
    {
        $this->extensions = array_map('strtolower', $extensions);
    }

    public function getExtensions()
    {
        return $this->extensions;
    }

    public function canRender($template)
    {
        $extension = pathinfo($template, PATHINFO_EXTENSION);
        if (!$extension) {
            foreach ($this->getExtensions() as $extension) {
                if ($this->canRender($template . '.' . $extension)) {
                    return true;
                }
            }
            return false;
        }

        return in_array(strtolower($extension), $this->extensions) && $this->fileLoader->exists($template);
    }
}
