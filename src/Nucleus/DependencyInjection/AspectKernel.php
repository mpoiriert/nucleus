<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\DependencyInjection;

use Go\Core\AspectContainer;
use Go\Core\AspectKernel as BaseKernel;

/**
 * Description of AspectKernel
 *
 * @author Martin
 */
class AspectKernel extends BaseKernel
{
    protected function configureAop(AspectContainer $container)
    {
        
    }
    
    public function init(array $options = array())
    {
        $cacheDir = array_key_exists('cacheDir', $options) ? $options['cacheDir'] : null;
        
        if($cacheDir && !is_dir($cacheDir)) {
          mkdir($cacheDir, 0777, true);
        }

        return parent::init($options);
    }

    public static function instanciate(array $options)
    {
        $isNew = is_null(static::$instance);

        $instance = static::getInstance();

        if ($isNew) {
            var_dump($options);
            $instance->init($options);
        }

        return $instance;
    }
}
