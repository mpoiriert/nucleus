<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\View;

use Nucleus\IService\View\IViewConciliator;
use Nucleus\IService\View\IView;

/**
 * Description of ServiceContainerViewConciliator
 *
 * @author Martin
 */
class ViewConciliator implements IViewConciliator
{
    /**
     *
     * @var IView[]
     */
    private $views;
    
    /**
     * 
     * @param IView[] $views
     */
    public function addViews(array $views)
    {
        $this->views = array_merge($this->views, $views);
    }

    public function clearAllViews()
    {
        $this->views = array();
    }

    public function getAllViews()
    {
        return $this->views;
    }

    public function getView($name)
    {
        if(!$this->hasView($name)) {
            return null;
        }
        
        return $this->views[$name];
    }

    public function hasView($name)
    {
        return array_key_exists($name, $this->views);
    }

    public function removeView($name)
    {
        unset($this->views[$name]);
    }

    public function setView($name, IView $view)
    {
        $this->views[$name] = $view;
    }

    public function setViews(array $views)
    {
        $this->views = $views;
    }    
}
