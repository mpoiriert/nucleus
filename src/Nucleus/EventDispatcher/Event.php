<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\EventDispatcher;

use Nucleus\IService\EventDispatcher\IEventDispatcherService;
use Nucleus\IService\EventDispatcher\IEvent;

/**
 * Description of Event
 *
 * @author Martin
 */
class Event implements IEvent
{
    /**
     * @var Boolean Whether no further event listeners should be triggered
     */
    private $propagationStopped = false;

    /**
     * @var EventDispatcher Dispatcher that dispatched this event
     */
    private $dispatcher;

    /**
     * @var string This event's name
     */
    private $name;
    private $subject;
    private $parameters;

    public function __construct($name, IEventDispatcherService $eventDispatcher, $subject, array $parameters = array())
    {
        $this->name = $name;
        $this->subject = $subject;
        $this->parameters = $parameters;
        $this->dispatcher = $eventDispatcher;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function hasParameter($name)
    {
        return array_key_exists($name, $this->parameters);
    }

    public function getParameter($name)
    {
        if (!array_key_exists($name, $this->parameters)) {
            
        }

        return $this->parameters[$name];
    }

    /**
     * Returns whether further event listeners should be triggered.
     *
     * @see Event::stopPropagation
     * @return Boolean Whether propagation was already stopped for this event.
     *
     * @api
     */
    public function isPropagationStopped()
    {
        return $this->propagationStopped;
    }

    /**
     * Stops the propagation of the event to further event listeners.
     *
     * If multiple event listeners are connected to the same event, no
     * further event listener will be triggered once any trigger calls
     * stopPropagation().
     *
     * @api
     */
    public function stopPropagation()
    {
        $this->propagationStopped = true;
    }

    /**
     * Returns the EventDispatcher that dispatches this Event
     *
     * @return EventDispatcherInterface
     *
     * @api
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Gets the event's name.
     *
     * @return string
     *
     * @api
     */
    public function getName()
    {
        return $this->name;
    }
}
