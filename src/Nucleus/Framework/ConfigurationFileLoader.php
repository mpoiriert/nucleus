<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Framework;

use Nucleus\IService\FileSystem\FileNotFoundException;
use RuntimeException;
use InvalidArgumentException;

/**
 * Description of Nucleus
 *
 * @author Martin
 */
class ConfigurationFileLoader
{
    private $loadedFiles;

    public function load($filename)
    {
        $this->loadedFiles = array();
        $result = $this->loadFile($filename);
        return $result;
    }

    public function getLoadedFiles()
    {
        return $this->loadedFiles;
    }

    private function getFilePath($filename, $basePath = null)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if (!$extension) {
            $filename .= "/nucleus.json";
        }
        switch (true) {
            case file_exists($filename):
                $file = $filename;
                break;
            case !is_null($basePath) && file_exists($basePath . DIRECTORY_SEPARATOR . $filename):
                $file = $basePath . DIRECTORY_SEPARATOR . $filename;
                break;
            default:
                $file = str_replace("\\", "/", stream_resolve_include_path($filename));
                break;
        }

        $file = realpath($file);
        if (!is_file($file) || !file_exists($file) || !is_readable($file)) {
            throw new FileNotFoundException($filename);
        }
        return $file;
    }
    
    private function loadFile($filename)
    {
        if (!is_array($filename)) {
            $filename = $this->getFilePath($filename);
            //This is to prevent infinite loop of including files
            if (in_array($filename, $this->loadedFiles)) {
                return array();
            }

            $this->loadedFiles[] = $filename;
            ob_start();
            include($filename);
            $content = ob_get_clean();
            $result = json_decode($content, true);
            $basePath = dirname($filename);
            if (json_last_error()) {
                throw new RuntimeException("Parsing of file [" . $filename . "] caused json error [" . json_last_error() . "]. Content: [" . $content . "]");
            }
        } else {
            $basePath = null;
            $result = $filename;
        }

        $result = $this->imports($result, $basePath);

        return $result;
    }

    private function imports($currentResult,$basePath)
    {
        if(!array_key_exists('imports', $currentResult)) {
            return $currentResult;
        }
        
        $files = $currentResult['imports'];
        unset($currentResult['imports']);
        $prepend = array();
        $append = array();
        foreach ($files as $fileInformation) {
            if(is_string($fileInformation)) {
                $fileInformation = array('file'=>$fileInformation);
            }
            
            if(!isset($fileInformation['file'])) {
                throw new InvalidArgumentException('No file specified');
            }
            
            $filename = $fileInformation['file'];
                
            $file = $this->getFilePath($filename, $basePath);
            $result = $this->loadFile($file);
            if(isset($fileInformation['append']) && $fileInformation['append']) {
                $append[] = $result;
            } else {
                $prepend[] = $result;
            }
        }
        
        return call_user_func_array(
            'array_deep_merge', 
            array_merge(
                $prepend,
                array($currentResult),
                $append
            )
        );
    }
}
