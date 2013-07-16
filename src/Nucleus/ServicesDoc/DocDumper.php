<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nucleus\ServicesDoc;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Dumper\Dumper;
use ReflectionClass;

/**
 * DocDumper dumps a JSON file containing documentation about services
 */
class DocDumper extends Dumper
{
    public function dump(array $options = array())
    {
        $data = array();

        $data['parameters'] = $this->prepareParameters($this->container->getParameterBag()->all(), 
            $this->container->isFrozen());

        $data['services'] = array();
        foreach ($this->container->getDefinitions() as $id => $definition) {
            $data['services'][] = $this->getServiceInfo($id, $definition);
        }

        $data['tags'] = array();
        foreach ($data['services'] as $service) {
            foreach ($service['tags'] as $tag) {
                if (!isset($data['tags'][$tag])) {
                    $data['tags'][$tag] = array();
                }
                $data['tags'][$tag][] = $service['id'];
            }
            $data['class'] = $service['class'];
        }

        return json_encode($data);
    }

    private function getServiceInfo($id, $definition)
    {
        
        $info = array('id' => $id);

        if ($definition->getClass()) {
            $info = array_merge($info, $this->getClassInfo(new ReflectionClass($definition->getClass())));
        }

        $info['dependencies'] = $this->getDependencies($definition);

        $info['tags'] = array();
        foreach ($definition->getTags() as $name => $tags) {
            $info['tags'][] = $name;
        }

        return $info;
    }

    private function getClassInfo($class)
    {
        $info = $this->parseDocComment($class->getDocComment());
        $info['class'] = $class->getName();

        $tags = array();
        $ignoredTags = array('Tag', 'Inject', 'Annotation');
        foreach ($info['docTags'] as $tag) {
            if (!in_array($tag[0], $ignoredTags)) {
                $tags[] = $tag;
            }
        }
        $info['docTags'] = $tags;

        return $info;
    }

    private function parseDocComment($comment)
    {
        $lines = explode("\n", substr(trim($comment), 2, -2));
        $lines = array_map(function($v) { return ltrim(trim($v), '* '); }, $lines);

        $desc = '';
        $tags = array();
        foreach ($lines as $line) {
            if (preg_match('/^@([a-zA-Z\-_0-9]+)(.*)$/', $line, $matches)) {
                $tags[] = array($matches[1], trim($matches[2]));
            } else {
                $desc .= "$line\n";
            }
        }

        $parts = array_map('trim', explode("\n\n", $desc, 2));
        $shortDesc = array_shift($parts);
        $longDesc = '';
        if (count($parts)) {
            $longDesc = array_shift($parts);
        }

        return array(
            'shortDesc' => $shortDesc,
            'longDesc' => $longDesc,
            'docTags' => $tags
        );
    }

    private function getDependencies($definition)
    {
        $deps = array();

        foreach ($definition->getMethodCalls() as $call) {
            list($method, $args) = $call;
            foreach ($args as $arg) {
                if ($arg instanceof Reference) {
                    $deps[] = (string) $arg;
                }
            }
        }

        foreach ($definition->getArguments() as $arg) {
            if ($arg instanceof Reference) {
                $deps[] = (string) $arg;
            }
        }

        foreach ($definition->getProperties() as $prop) {
            if ($prop instanceof Reference) {
                $deps[] = (string) $prop;
            }
        }

        return $deps;
    }

    private function prepareParameters($parameters, $escape = true)
    {
        $filtered = array();
        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                $value = $this->prepareParameters($value, $escape);
            } elseif ($value instanceof Reference || is_string($value) && 0 === strpos($value, '@')) {
                $value = '@'.$value;
            }

            $filtered[$key] = $value;
        }

        return $escape ? $this->escape($filtered) : $filtered;
    }

    private function escape($arguments)
    {
        $args = array();
        foreach ($arguments as $k => $v) {
            if (is_array($v)) {
                $args[$k] = $this->escape($v);
            } elseif (is_string($v)) {
                $args[$k] = str_replace('%', '%%', $v);
            } else {
                $args[$k] = $v;
            }
        }

        return $args;
    }
}
