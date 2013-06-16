<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Session;

use Nucleus\DependencyInjection\IServiceContainerGeneratorAnnotation;
use Nucleus\DependencyInjection\GenerationContext;

/**
 * Description of BoundToSession
 *
 * @Annotation
 */
class BoundToSession implements IServiceContainerGeneratorAnnotation
{

    public function processContainerBuilder(GenerationContext $context)
    {
        $definition = $context->getServiceDefinition();
        $serviceName = $context->getServiceName();

        $currentCode = $definition->getCodeInitalization();
        $serviceBinderAssignation = '
    $sessionServiceBinder = $serviceContainer->getServiceByName("sessionServiceBinder");
';
        if (strpos($currentCode, $serviceBinderAssignation) === false) {
            $currentCode .= $serviceBinderAssignation;
        }
        $currentCode .= '
    $sessionServiceBinder->addBindingAttribute("' . $serviceName . '","' . $context->getParsingContextName() . '");
';
        $restoreFromSession = '
    $sessionServiceBinder->restoreFromSession($service,"' . $serviceName . '");
';
        $finalCode = str_replace($restoreFromSession, "", $currentCode);
        $finalCode .= $restoreFromSession;

        $definition->setCodeInitialization($finalCode);
    }
}