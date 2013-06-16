<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\DependencyInjection;

/**
 * @Annotation
 */
class Tag extends \Nucleus\IService\DependencyInjection\Tag implements IServiceContainerGeneratorAnnotation
{

    public function processContainerBuilder(GenerationContext $context)
    {
        $context->getServiceDefinition()->addTag($this->getTagName());
    }
}
