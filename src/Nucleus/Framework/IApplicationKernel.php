<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Framework;

/**
 * Description of IApplicationKernel
 *
 * @author Martin
 */
interface IApplicationKernel
{
    /**
     * @return Nucleus
     */
    public function getNucleus();
}
