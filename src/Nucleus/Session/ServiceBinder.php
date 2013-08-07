<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Session;

use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Description of BoundToSessionInjecter
 *
 * @author Martin
 */
class ServiceBinder
{
    private $serviceAttributes = array();

    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    private $session;
    private $services = array();

    public function setBindingAttributes($serviceName, $attributeNames)
    {
        $this->serviceAttributes[$serviceName] = $attributeNames;
    }

    public function addBindingAttribute($serviceName, $attributeName)
    {
        $this->serviceAttributes[$serviceName][] = $attributeName;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * 
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function setSession(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function restoreFromSession($service, $serviceName)
    {

        if (!isset($this->serviceAttributes[$serviceName])) {
            return;
        }
        $this->services[$serviceName] = $service;
        $attributes = $this->serviceAttributes[$serviceName];

        foreach ($attributes as $name) {
            $sessionVariableName = $this->getServiceVariableName($serviceName, $name);
            if (!$this->session->has($sessionVariableName)) {
                continue;
            }
            if (!($property = $this->getProperty($name, $service))) {
                continue;
            }

            $property->setAccessible(true);

            $property->setValue($service, $this->session->get($sessionVariableName));

            if (!$this->isPropertyAccesible($property)) {
                $property->setAccessible(false);
            }
        }
    }

    public function setToSession($service, $serviceName)
    {
        if (!isset($this->serviceAttributes[$serviceName])) {
            return;
        }
        $attributes = $this->serviceAttributes[$serviceName];

        foreach ($attributes as $name) {
            if (!($property = $this->getProperty($name, $service))) {
                continue;
            }
            $property->setAccessible(true);

            $this->session->set(
                $this->getServiceVariableName($serviceName, $name), $property->getValue($service)
            );

            if (!$this->isPropertyAccesible($property)) {
                $property->setAccessible(false);
            }
        }
    }

    private function isPropertyAccesible(ReflectionProperty $property)
    {
        return !($property->isPrivate() || $property->isProtected());
    }

    private function getProperty($name, $object)
    {
        $class = new ReflectionClass(get_class($object));

        do {
            if ($class->hasProperty($name)) {
                return $class->getProperty($name);
            }
        } while ($class = $class->getParentClass());

        return null;
    }

    /**
     * @\Nucleus\IService\EventDispatcher\Listen("Session.shutdown")
     */
    public function setBindServicesToSession()
    {
        foreach ($this->services as $name => $service) {
            $this->setToSession($service, $name);
        }
    }

    private function getServiceVariableName($serviceName, $variableName)
    {
        return 'binding.' . $serviceName . '.' . $variableName;
    }
}
