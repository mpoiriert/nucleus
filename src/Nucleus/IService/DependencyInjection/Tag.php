<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\DependencyInjection;

/**
 * Description of Tag
 *
 * @Annotation
 * @Attributes({
 *   @Attribute("tagName", type = "string"),
 * })
 */
class Tag
{

    public function __construct($values)
    {
        $this->tagName = $values['value'];
    }

    public function getTagName()
    {
        return $this->tagName;
    }
}