<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\DependencyInjection;

/**
 *
 * @author Martin
 */
interface IServiceContainerGeneratorAnnotation
{

    public function processContainerBuilder(GenerationContext $context);
}
