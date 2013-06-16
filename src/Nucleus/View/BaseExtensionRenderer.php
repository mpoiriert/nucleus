<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\View;

use \Nucleus\IService\View\IViewRendererService;

/**
 * Description of BaseExtensionRenderer
 *
 * @author Martin
 */
abstract class BaseExtensionRenderer implements IViewRendererService
{
    private $extensions;

    /**
     * @var \Nucleus\View\FileSystemLoader
     */
    private $fileLoader;

    /**
     * @param \Nucleus\View\FileSystemLoader $loader
     * 
     * @Inject
     */
    public function setFileLoader(FileSystemLoader $viewFileLoader)
    {
        $this->fileLoader = $viewFileLoader;
    }

    /**
     * @return \Nucleus\View\FileSystemLoader
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

    public function canRender($file)
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        if (!$extension) {
            foreach ($this->getExtensions() as $extension) {
                if ($this->canRender($file . '.' . $extension)) {
                    return true;
                }
            }
            return false;
        }

        return in_array(strtolower($extension), $this->extensions) && $this->fileLoader->exists($file);
    }
}
