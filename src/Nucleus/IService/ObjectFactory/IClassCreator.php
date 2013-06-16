<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\ObjectFactory;

use Nucleus\ObjectFactory\ChildClassDefinition;

/**
 * Description of IBuilder
 *
 * @author Martin
 */
interface IClassCreator
{

    public function modifyCode(ChildClassDefinition $childClassDefinition);
}
