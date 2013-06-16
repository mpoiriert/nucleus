<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\FileSystem;

use Symfony\Component\Filesystem\Filesystem;
use Nucleus\IService\FileSystem\IFileSystemService;

/**
 * This class is there just to have a interface for the service
 *
 * @author Martin
 */
class FileSystemService extends Filesystem implements IFileSystemService
{
    //put your code here
}
