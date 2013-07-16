<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\DependencyInjection;

/**
 * Description of TagAnnotationContainerGenerator
 *
 * @author Martin
 */
class TagAnnotationContainerGenerator implements IServiceContainerGeneratorAnnotation
{
    /**
     * @param GenerationContext $context
     */
    public function processContainerBuilder(GenerationContext $context)
    {
        $annotation = $context->getAnnotation();
        /* @var $annotation \Nucleus\IService\DependencyInjection\Tag */
        $context->getServiceDefinition()->addTag($annotation->name);
    }
}
