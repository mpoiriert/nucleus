<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\AssetManager;

use Nucleus\IService\FileSystem\IFileSystemService;
use Nucleus\IService\AssetManager\IFilePersister;

/**
 * Description of FilePersister
 *
 * @author Martin
 */
class FilePersister implements IFilePersister
{
    private $rootDirectory;

    /**
     * @var \Nucleus\IService\FileSystem\IFileSystemService
     */
    private $fileSystem;

    /**
     * @param \Nucleus\IService\FileSystem\IFileSystemService $fileSystem
     * 
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function initialize(IFileSystemService $fileSystem)
    {
        $this->fileSystem = $fileSystem;
    }

    /**
     * @param strin $directory
     * 
     * @\Nucleus\IService\DependencyInjection\Inject(directory="$[assetManager][rootDirectory]")
     */
    public function setRootDirectory($directory)
    {
        $this->rootDirectory = $directory;
    }

    public function persist($filePath, $content)
    {
        $this->fileSystem->dumpFile($this->rootDirectory . '/' . $filePath, $content);
        return true;
    }

    public function recover($path)
    {
        return file_get_contents($this->rootDirectory . $path);
    }

    public function exists($path)
    {
        return $this->fileSystem->exists($this->rootDirectory . $path);
    }
}
