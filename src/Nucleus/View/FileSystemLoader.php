<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\View;

use Twig_Loader_Filesystem;

/**
 * Description of FileSystemLoader
 *
 * @author Martin
 */
class FileSystemLoader extends Twig_Loader_Filesystem
{
    /**
     * @param array $configuration
     * 
     * @\Nucleus\IService\DependencyInjection\Inject(configuration="$")
     */
    public function initialize(array $configuration)
    {
        if (isset($configuration['paths'])) {
            $this->setPaths($configuration['paths']);
        }
    }

    public function exists($fileName)
    {
        return file_exists($fileName) || parent::exists($fileName);
    }
    
    public function findTemplate($name)
    {
        // normalize name
        $name = preg_replace('#/{2,}#', '/', strtr((string)$name, '\\', '/'));

        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }
        
        if (is_file($name)) {
            return $this->cache[$name] = $name;
        }
        
        return parent::findTemplate($name);
    }

    public function getFullPath($fileName)
    {
        return $this->findTemplate($fileName);
    }
}
