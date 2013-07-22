<?php

namespace Nucleus\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Nucleus\Framework\ConfigurationFileLoader;
use Nucleus\Annotation\AnnotationParser;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use ReflectionClass;
use Nucleus\Framework\DnaConfiguration;

class NucleusCompilerPass implements CompilerPassInterface
{
    /**
     * @var DnaConfiguration
     */
    private $dnaConfiguration;
    
    private $configuration;
    
    /**
     * @var ContainerBuilder
     */
    private $container;
    private $loaderFiles;

    /**
     * @var \Nucleus\Annotation\AnnotationParser 
     */
    private $annotationParser;

    public function __construct(DnaConfiguration $dna)
    {
        $this->dnaConfiguration = $dna;
        $fileLoader = new ConfigurationFileLoader();
        $dna->prependConfiguration(__DIR__ . "/nucleus.json");
        $configuration = $fileLoader->load($dna->getConfiguration());
        //We set/override the debug value base on the debug in dna configuration
        //So it can be reuse in the service container
        $configuration['services']['configuration']['configuration']['debug'] = $dna->getDebug();
        $this->dnaConfiguration->setConfiguration($configuration);
        $this->configuration = $this->dnaConfiguration->getConfiguration();
        $this->loaderFiles = $fileLoader->getLoadedFiles();
        $this->setDefaultConfiguration();
    }

    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function process(ContainerBuilder $container)
    {
        $this->container = $container;

        foreach ($this->loaderFiles as $filePath) {
            $container->addResource(new FileResource($filePath));
        }

        $annotationParser = $this->getAnnotationParser();
        $this->prepareDefinition();
        foreach ($this->configuration['services'] as $name => $serviceConfiguration) {
            if (isset($serviceConfiguration['disabled']) && $serviceConfiguration['disabled']) {
                continue;
            }
            $definition = $this->container->getDefinition($name);
           
            $parsingResult = $annotationParser->parse($serviceConfiguration['class']);
            
            $annotations = $parsingResult->getAllAnnotations();

            foreach ($annotations as $parsingNode) {
                $this->addFileResource(get_class($parsingNode['annotation']));
                $generationContext = new GenerationContext($container, $name, $definition, $parsingNode);
                if($parsingNode['annotation'] instanceof IServiceContainerGeneratorAnnotation) {
                    $parsingNode['annotation']->processContainerBuilder($generationContext);
                } elseif(!is_null($builder = $this->getAnnotationContainerBuilder($parsingNode['annotation']))){
                    $builder->processContainerBuilder($generationContext);
                }
            }
            
            if(array_key_exists('configuration', $serviceConfiguration)) {
                $this->container->getDefinition("configuration")
                    ->addMethodCall(
                        'merge',
                        array(array($name=>$serviceConfiguration['configuration']))
                    );
            }
        }
    }
    
    private function getAnnotationContainerBuilder($annotation)
    {
        $annotationClass = get_class($annotation);
        if(isset($this->configuration['nucleus']['annotationContainerGenerator'][$annotationClass]['class'])) {
            $class = $this->configuration['nucleus']['annotationContainerGenerator'][$annotationClass]['class'];
            return new $class();
        }
        
    }

    private function setDefaultConfiguration()
    {
        $defaultConfiguration = array();
        $defaultConfiguration['services']['aspectKernel']['arguments'] = array(
            $this->dnaConfiguration->getAspectConfiguration()
        );
        
        $defaultConfiguration['services']['configuration']['configuration']['generatedDirectory'] = $this->dnaConfiguration->getCachePath();
        
        $this->configuration = array_deep_merge($defaultConfiguration, $this->configuration);
    }

    /**
     * @return \Nucleus\Annotation\AnnotationParser
     */
    private function getAnnotationParser()
    {
        if (is_null($this->annotationParser)) {
            $this->annotationParser = new AnnotationParser();
            foreach ($this->configuration['nucleus']['annotationNamespaces'] as $namespace) {
                $this->annotationParser->addNamespace($namespace);
            }
        }

        return $this->annotationParser;
    }

    private function prepareDefinition()
    {
        if (isset($this->configuration['services']['configuration']['configuration'])) {
            foreach ($this->configuration['services']['configuration']['configuration'] as $key => $value) {
                $this->container->setParameter($key, $this->resolveServices($value));
            }
        }
        
        $definition = new Definition();
        $definition->setClass("Nucleus\DependencyInjection\BaseServiceContainer");
        $definition->setFactoryService("service_container");
        $definition->setFactoryMethod("getThis");
        $this->container->setDefinition("serviceContainer", $definition);
        
        uksort($this->configuration['services'], function($a, $b) {
            if($a == 'aspectKernel') {
                return -1;
            }
            
            if($b == 'aspectKernel') {
                return 1;
            }
            
            if (strpos($a, 'aspect.') === 0 && strpos($b, 'aspect.') === false) {
                return -1;
            }

            if (strpos($a, 'aspect.') === false && strpos($b, 'aspect.') === 0) {
                return 1;
            }
            return strcmp($a, $b);
        });

        foreach ($this->configuration['services'] as $id => $service) {
            $this->parseDefinition($id, $service);
            if(strpos($id,'aspect.') === 0 && (!isset($service['disabled']) || !$service['disabled'])) {
                $class = $definition = $this->container->getDefinition($id)->getClass();
                $service = new $class(); 
                $aspectContainer = $this->container->get('aspectKernel')->getContainer();
                $aspectContainer->registerAspect($service);
            }
        }
    }

    private function addFileResource($class)
    {
        if (!($class instanceof ReflectionClass)) {
            $class = new ReflectionClass($class);
        }

        $this->container->addResource(new FileResource($class->getFileName()));
        if ($class->getParentClass()) {
            $modificationDates[] = $this->addFileResource($class->getParentClass());
        }

        foreach ($class->getInterfaces() as $interface) {
            $modificationDates[] = $this->addFileResource($interface);
        }
    }

    /**
     * Parses a definition.
     *
     * @param string $id
     * @param array  $service
     * @param string $file
     *
     * @throws InvalidArgumentException When tags are invalid
     */
    private function parseDefinition($id, $service)
    {
        if (isset($service['disabled']) && $service['disabled']) {
            return;
        }

        if (is_string($service) && 0 === strpos($service, '@')) {
            $this->container->setAlias($id, substr($service, 1));

            return;
        } elseif (isset($service['alias'])) {
            $public = !array_key_exists('public', $service) || (Boolean) $service['public'];
            $this->container->setAlias($id, new Alias($service['alias'], $public));

            return;
        }

        if (isset($service['parent'])) {
            $definition = new DefinitionDecorator($service['parent']);
        } else {
            $definition = new Definition();
        }

        if (isset($service['class'])) {
            $this->addFileResource($service['class']);
            $definition->setClass($service['class']);
        }

        if (isset($service['scope'])) {
            $definition->setScope($service['scope']);
        }

        if (isset($service['synthetic'])) {
            $definition->setSynthetic($service['synthetic']);
        }

        if (isset($service['synchronized'])) {
            $definition->setSynchronized($service['synchronized']);
        }

        if (isset($service['lazy'])) {
            $definition->setLazy($service['lazy']);
        }

        if (isset($service['public'])) {
            $definition->setPublic($service['public']);
        }

        if (isset($service['abstract'])) {
            $definition->setAbstract($service['abstract']);
        }

        if (isset($service['factory_class'])) {
            $definition->setFactoryClass($service['factory_class']);
        }

        if (isset($service['factory_method'])) {
            $definition->setFactoryMethod($service['factory_method']);
        }

        if (isset($service['factory_service'])) {
            $definition->setFactoryService($service['factory_service']);
        }

        if (isset($service['file'])) {
            $definition->setFile($service['file']);
        }

        if (isset($service['arguments'])) {
            $definition->setArguments($this->resolveServices($service['arguments']));
        }

        if (isset($service['properties'])) {
            $definition->setProperties($this->resolveServices($service['properties']));
        }

        if (isset($service['configurator'])) {
            if (is_string($service['configurator'])) {
                $definition->setConfigurator($service['configurator']);
            } else {
                $definition->setConfigurator(array($this->resolveServices($service['configurator'][0]), $service['configurator'][1]));
            }
        }

        if (isset($service['calls'])) {
            foreach ($service['calls'] as $call) {
                $args = isset($call[1]) ? $this->resolveServices($call[1]) : array();
                $definition->addMethodCall($call[0], $args);
            }
        }

        if (isset($service['tags'])) {
            if (!is_array($service['tags'])) {
                throw new InvalidArgumentException(sprintf('Parameter "tags" must be an array for service "%s".', $id));
            }

            foreach ($service['tags'] as $tag) {
                if (!isset($tag['name'])) {
                    throw new InvalidArgumentException(sprintf('A "tags" entry is missing a "name" key for service "%s".', $id));
                }

                $name = $tag['name'];
                unset($tag['name']);

                foreach ($tag as $attribute => $value) {
                    if (!is_scalar($value)) {
                        throw new InvalidArgumentException(sprintf('A "tags" attribute must be of a scalar-type for service "%s", tag "%s".', $id, $name));
                    }
                }

                $definition->addTag($name, $tag);
            }
        }

        $this->container->setDefinition($id, $definition);
    }
    
    private function resolveServices($value)
    {
        if (is_array($value)) {
            $value = array_map(array($this, 'resolveServices'), $value);
        } elseif (is_string($value) &&  0 === strpos($value, '@')) {
            if (0 === strpos($value, '@@')) {
                $value = substr($value, 1);
                $invalidBehavior = null;
            } elseif (0 === strpos($value, '@?')) {
                $value = substr($value, 2);
                $invalidBehavior = ContainerInterface::IGNORE_ON_INVALID_REFERENCE;
            } else {
                $value = substr($value, 1);
                $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;
            }

            if ('=' === substr($value, -1)) {
                $value = substr($value, 0, -1);
                $strict = false;
            } else {
                $strict = true;
            }

            if (null !== $invalidBehavior) {
                $value = new Reference($value, $invalidBehavior, $strict);
            }
        }

        return $value;
    }
}