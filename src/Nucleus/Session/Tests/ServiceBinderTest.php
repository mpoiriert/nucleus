<?php

namespace Nucleus\Security\Tests;

use Nucleus\Session\ServiceBinder;

class SessionServiceBinderTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var Nucleus\Session\SessionServiceBinder
     */
    private $sessionServiceBinder;

    public function setUp()
    {
        $this->sessionServiceBinder = new ServiceBinder();
    }

    private function getNewSession()
    {
        return new \Symfony\Component\HttpFoundation\Session\Session(
            new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage()
        );
    }

    public function test()
    {
        $properties = array(
            'private',
            'protected',
            'public'
        );

        $session = $this->getNewSession();
        $serviceToBoundTo = new ServiceToBoundTo();
        $this->sessionServiceBinder->setBindingAttributes(
            'test', $properties
        );

        $this->sessionServiceBinder->setSession($session);
        $this->sessionServiceBinder->restoreFromSession($serviceToBoundTo, 'test');

        foreach ($properties as $name) {
            $this->assertEquals($name . 'Default', $serviceToBoundTo->getProperty($name));
        }

        foreach ($properties as $name) {
            $serviceToBoundTo->setProperty($name, $name . 'Value');
        }

        $this->sessionServiceBinder->setToSession($serviceToBoundTo, 'test');

        $serviceToBoundTo2 = new ServiceToBoundTo();

        foreach ($properties as $name) {
            $this->assertEquals($name . 'Default', $serviceToBoundTo2->getProperty($name));
        }

        $this->sessionServiceBinder->restoreFromSession($serviceToBoundTo2, 'test');

        foreach ($properties as $name) {
            $this->assertEquals($name . 'Value', $serviceToBoundTo2->getProperty($name));
        }
    }
}

class ServiceToBoundTo
{
    private $private = 'privateDefault';
    protected $protected = 'protectedDefault';
    public $public = 'publicDefault';

    public function getProperty($name)
    {
        return $this->{$name};
    }

    public function setProperty($name, $value)
    {
        $this->{$name} = $value;
    }
}