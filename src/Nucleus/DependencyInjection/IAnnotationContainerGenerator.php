<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\DependencyInjection;

/**
 * Description of IAnnotationContainerGenerator
 *
 * @author Martin
 */
interface IAnnotationContainerGenerator
{
    public function processContainerBuilder(GenerationContext $context);
}
