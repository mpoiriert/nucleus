<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Nucleus\CommandLine;
use Nucleus\DependencyInjection\BaseAnnotationContainerGenerator;
use Nucleus\DependencyInjection\GenerationContext;
use Sami\Parser\DocBlockParser;

/**
 * Description of ConsolableContainerGenerator
 *
 * @author AxelBarbier
 */
class ConsolableContainerGenerator extends BaseAnnotationContainerGenerator
{
    
    /**
     * 
     * @param GenerationContext $context
     * @param \Nucleus\IService\CommandLine\Consolable $annotation
     */
    public function generate(GenerationContext $context, $annotation)
    {
        $docParser      = new DocBlockParser();
        $serviceName    = $context->getServiceName();
        $methodName     = $context->getParsingContextName();
        $definition     = $context->getContainerBuilder()->getDefinition($serviceName);
        $shortDesc      = 'N/A';
        $reflectedMethod = new \ReflectionMethod($definition->getClass(), $methodName);
        $methodComment = $reflectedMethod->getDocComment();
        
        if ($methodComment !== false) {
            $docMethod = $docParser->parse($methodComment);
            $shortDesc  = $docMethod->getShortDesc();
        }
        $paramsArray = array();
        $paramArrayComments = self::extractParamDocComment($docMethod->getTag('param'));
        
        foreach($reflectedMethod->getParameters() as $reflectionParameter){
            $paramComment = 'N/A';
            if(isset($paramArrayComments[$reflectionParameter->getName()])){
                $paramComment = $paramArrayComments[$reflectionParameter->getName()]['comment'];
            }
            
            $paramsArray[$reflectionParameter->getName()]['optional'] = false;
            if ($reflectionParameter->isDefaultValueAvailable()) {
                $paramsArray[$reflectionParameter->getName()]['optional'] = true;
            }
            $paramsArray[$reflectionParameter->getName()]['comment'] = $paramComment;
        }
        if(!empty($annotation->name)){
            $name = $annotation->name;
        }
        else {
            $name = $serviceName.':'.$methodName;
        }
        $context->getContainerBuilder()->getDefinition("console")->addMethodCall("addCommand", 
                array("applicatiomName" => $name, "serviceName" => $serviceName,
                    "methodName" => $methodName, "shortDesc" => $shortDesc, "paramsMethod" => $paramsArray)
                );
        
    }
    
    private function extractParamDocComment($tagArray){
        $paramArray = array();
        if(!is_array($tagArray)){
            return false;
        }
        foreach($tagArray as $tag){
            if(is_array($tag)){
                $paramArray[$tag[1]] = array();
                if(isset($tag[2]) && !empty($tag[2]))
                    $paramArray[$tag[1]]['comment'] = $tag[2];
                else
                    $paramArray[$tag[1]]['comment'] = 'N/A';
            }
        }
        return $paramArray;
    }
}

