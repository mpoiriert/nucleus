<?php

namespace Nucleus\DependencyInjection;

use Symfony\Component\DependencyInjection\Dumper\PhpDumper as BasePhpDumper;
use ReflectionObject;
use ReflectionMethod;

class PhpDumper extends BasePhpDumper
{
    /**
     * @var ReflectionObject 
     */
    private $reflectionObject;

    /**
     *
     * @var ReflectionMethod[]
     */
    private $reflectionMethods;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected $container;

    public function dump(array $options = array())
    {
        $this->reflectionObject = new ReflectionObject($this);
        $options = array_merge(array(
            'class' => 'ProjectServiceContainer',
            'base_class' => '\Nucleus\DependencyInjection\BaseServiceContainer',
            ), $options);

        $code = $this->call('startClass', $options['class'], $options['base_class']);
        $code .= $this->addCustom($options);

        if ($this->container->isFrozen()) {
            $code .= $this->call('addFrozenConstructor');
        } else {
            $code .= $this->call('addConstructor');
        }

        $code .=
            $this->call('addServices') .
            $this->call('addDefaultParametersMethod') .
            $this->call('endClass') .
            $this->call('addProxyClasses')
        ;

        return $code;
    }

    protected function addCustom($options)
    {
        $tags = array();
        foreach ($this->container->findTags() as $tag) {
            $tags[$tag] = array_keys($this->container->findTaggedServiceIds($tag));
        }

        $configurations = array();
        $disabled = array();
        foreach ($options['nucleus']['services'] as $serviceName => $serviceDefinition) {
            $configurations[$serviceName] = null;
            if (isset($serviceDefinition['disabled']) && $serviceDefinition['disabled']) {
                $disabled[] = $serviceName;
            }
            if (isset($serviceDefinition['configuration'])) {
                $configurations[$serviceName] = $serviceDefinition['configuration'];
            }
        }

        return '
    protected $tags = ' . var_export($tags, true) . ';
      
    protected $disabled = ' . var_export($disabled, true) . ';
      
    protected $serviceConfigurations = ' . var_export($configurations, true) . ';
';
    }

    protected function call($method)
    {
        $args = func_get_args();
        array_shift($args);
        if (!isset($this->reflectionMethods[$method])) {
            $this->reflectionMethods[$method] = $this->reflectionObject->getMethod($method);
            $this->reflectionMethods[$method]->setAccessible(true);
        }

        return $this->reflectionMethods[$method]->invokeArgs($this, $args);
    }
}
