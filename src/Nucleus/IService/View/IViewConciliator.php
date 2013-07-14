<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\View;

/**
 * Description of IViewConciliator
 *
 * @author Martin
 */
interface IViewConciliator
{
    /**
     * The service name use as a reference
     */
    const NUCLEUS_SERVICE_NAME = 'viewConciliator';
    
    /**
     * @param IView[] $views
     */
    public function addViews(array $views);

    /**
     * @param string $name
     * 
     * @return IView
     */
    public function getView($name);
    
    /**
     * @param type $name
     * @param IView $view
     */
    public function setView($name, IView $view);
    
    /**
     * @param string $name
     * 
     * @return boolean
     */
    public function hasView($name);
    
    /**
     * @return IView[]
     */
    public function getAllViews();
    
    /**
     * @param string $name
     */
    public function removeView($name);
    
    /**
     * 
     */
    public function clearAllViews();
    
    /**
     * Replace all the existing with new ones
     * 
     * @param IViews[] $views
     */
    public function setViews(array $views);
}
