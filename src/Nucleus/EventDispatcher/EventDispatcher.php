<?php

namespace Nucleus\EventDispatcher;

use Nucleus\IService\EventDispatcher\IEventDispatcherService;
use Symfony\Component\EventDispatcher\EventDispatcher as ProxiedEventDispatcher;
use Nucleus\IService\EventDispatcher\IEvent;
use Nucleus\Framework\Nucleus;
use Nucleus\IService\Invoker\IInvokerService;

/**
 * @\Nucleus\IService\DependencyInjection\Tag(name="autoStart")
 */
class EventDispatcher implements IEventDispatcherService
{
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher 
     */
    private $eventDispatcher = null;

    /**
     * @var \Nucleus\IService\Invoker\IInvokerService
     */
    private $invoker = null;

    public function __construct()
    {
        $this->eventDispatcher = new ProxiedEventDispatcher();
    }

    /**
     * @param Nucleus\IService\Invoker\IInvokerService
     * 
     * @\Nucleus\IService\DependencyInjection\Inject
     */
    public function setInvoker(IInvokerService $invoker)
    {
        $this->invoker = $invoker;
    }

    public function addListener($eventName, $listener, $priority = 0)
    {
        $this->eventDispatcher->addListener($eventName, $listener, $priority);
    }

    public function dispatch($eventName, $subject = null, array $parameters = array())
    {
        $event = new Event($eventName, $this, $subject, $parameters);

        $this->doDispatch($this->getListeners($eventName), $event);

        return $event;
    }

    private function doDispatch($listeners, IEvent $event)
    {
        foreach ($listeners as $listener) {
            $this->invoker->invoke($listener, $event->getParameters(), array($event, $event->getSubject()));
            if ($event->isPropagationStopped()) {
                break;
            }
        }
    }

    public function getListeners($eventName = null)
    {
        return array_filter($this->eventDispatcher->getListeners($eventName));
    }

    public function hasListeners($eventName = null)
    {
        return $this->eventDispatcher->hasListeners($eventName);
    }

    public function removeListener($eventName, $listener)
    {
        return $this->eventDispatcher->removeListener($eventName, $listener);
    }

    /**
     * @param mixed $configuration
     * @return IEventDispatcherService
     */
    public static function factory($configuration = null)
    {
        if (is_null($configuration)) {
            $configuration = __DIR__ . '/nucleus.json';
        }

        return Nucleus::serviceFactory($configuration, self::NUCLEUS_SERVICE_NAME);
    }
}