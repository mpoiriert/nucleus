<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\ObjectFactory;

/**
 * Description of IBuilder
 *
 * @author Martin
 */
interface IObjectBuilder
{

    public function initializeObject($mixed, array $contextParameters = array());
}
