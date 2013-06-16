<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Framework;

use Nucleus\IService\FileSystem\FileNotFoundException;
use RuntimeException;

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
        switch (true) {
            case file_exists($filename):
                $file = $filename;
                break;
            case !is_null($basePath) && file_exists($basePath . DIRECTORY_SEPARATOR . $filename):
                $file = $basePath . DIRECTORY_SEPARATOR . $filename;
                break;
            default:
                $path = $filename;
                $extension = pathinfo($filename, PATHINFO_EXTENSION);
                if (!$extension) {
                    $path .= "/nucleus.json";
                }

                $file = str_replace("\\", "/", stream_resolve_include_path($path));
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

        if (array_key_exists('imports', $result)) {
            $result = array_deep_merge($this->imports($result['imports'], $basePath), $result);
            unset($result['imports']);
        }

        return $result;
    }

    private function imports($files, $basePath)
    {
        $result = array();
        foreach ($files as $file) {
            $file = $this->getFilePath($file, $basePath);
            $result[] = $this->loadFile($file);
        }

        return call_user_func_array('array_deep_merge', $result);
    }
}
