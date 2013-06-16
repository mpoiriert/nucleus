<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Session;

use Symfony\Component\HttpFoundation\Session\Session as BaseSession;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;
use Nucleus\IService\DependencyInjection\ILifeCycleAware;
use Nucleus\IService\EventDispatcher\IEventDispatcherService;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;

/**
 * Description of Session
 *
 * @author Martin
 */
class Session implements ILifeCycleAware, SessionInterface
{
    /**
     * @var \Nucleus\IService\EventDispatcher\IEventDispatcherService 
     */
    private $eventDispatcher;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\Session
     */
    private $session;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface 
     */
    private $sessionStorage;

    /**
     * @param \Nucleus\Session\Session\EventDispatcher $eventDispatcher
     * 
     * @Inject
     */
    public function initialize(SessionStorageInterface $sessionStorage, IEventDispatcherService $eventDispatcher)
    {
        $this->sessionStorage = $sessionStorage;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Session\Session
     */
    private function getSymfonySession()
    {
        if (is_null($this->session)) {
            $this->session = new BaseSession($this->sessionStorage);
        }

        return $this->session;
    }

    public function start()
    {
        $this->getSymfonySession()->start();
    }

    public function serviceStart()
    {
        
    }

    public function serviceShutdown()
    {
        $this->eventDispatcher->dispatch('Session.shutdown', $this);
    }

    public function all()
    {
        return $this->getSymfonySession()->all();
    }

    public function clear()
    {
        return $this->getSymfonySession()->clear();
    }

    public function get($name, $default = null)
    {
        return $this->getSymfonySession()->get($name, $default);
    }

    public function getBag($name)
    {
        return $this->getSymfonySession()->getBag($name);
    }

    public function getId()
    {
        return $this->getSymfonySession()->getId();
    }

    public function getMetadataBag()
    {
        return $this->getSymfonySession()->getMetadataBag();
    }

    public function getName()
    {
        return $this->getSymfonySession()->getName();
    }

    public function has($name)
    {
        return $this->getSymfonySession()->has($name);
    }

    public function invalidate($lifetime = null)
    {
        return $this->getSymfonySession()->invalidate($lifetime);
    }

    public function isStarted()
    {
        return $this->getSymfonySession()->isStarted();
    }

    public function migrate($destroy = false, $lifetime = null)
    {
        return $this->getSymfonySession()->migrate($destroy, $lifetime);
    }

    public function registerBag(SessionBagInterface $bag)
    {
        return $this->getSymfonySession()->registerBag($bag);
    }

    public function remove($name)
    {
        return $this->getSymfonySession()->remove($name);
    }

    public function replace(array $attributes)
    {
        return $this->getSymfonySession()->replace($attributes);
    }

    public function save()
    {
        return $this->getSymfonySession()->save();
    }

    public function set($name, $value)
    {
        return $this->getSymfonySession()->set($name, $value);
    }

    public function setId($id)
    {
        return $this->getSymfonySession()->setId($id);
    }

    public function setName($name)
    {
        return $this->getSymfonySession()->setName($name);
    }
}
