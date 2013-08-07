<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\AssetManager;

use Nucleus\IService\FileSystem\IFileSystemService;

/**
 * Description of AssetCopier
 *
 * @author Martin
 */
class AssetCopier
{
    /**
     * @var \Nucleus\IService\FileSystem\IFileSystemService
     */
    private $fileSystem;
    
    /**
     * @var array
     */
    private $configuration;
    
    /**
     * @param array $configuration
     * 
     * @\Nucleus\IService\DependencyInjection\Inject(configuration="$",rootDirectory="$[assetManager][rootDirectory]")
     */
    public function setConfiguration(array $configuration, $rootDirectory)
    {
        $this->configuration = $configuration;
        $this->rootDirectory = $rootDirectory . '/nucleus/asset';
    }
    
    /**
     * @param \Nucleus\IService\FileSystem\IFileSystemService $fileSystem
     * 
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function intilalize(IFileSystemService $fileSystem)
    {
        $this->fileSystem = $fileSystem;
    }
    
    /**
     * @\Nucleus\IService\EventDispatcher\Listen(eventName="ServiceContainer.postDump")
     */
    public function mirror()
    {
        $this->fileSystem->remove(glob($this->rootDirectory . '/*'));
        foreach($this->configuration['toMirror'] as $sectionName => $mirrorConfiguration) {
            $endTarget =  isset($mirrorConfiguration['target']) ? $mirrorConfiguration['target'] : $sectionName;
            $target = $this->rootDirectory . '/' . $endTarget;
            $source = $mirrorConfiguration['source'];
            $sourcePath = stream_resolve_include_path($source);
            $this->fileSystem->mirror($sourcePath, $target, null, array('copy_on_windows'=>true,'delete'=>false));
        }
    }
}
