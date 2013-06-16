EventDispatcher
===============

EventDispatcher implements a lightweight version of the Observer design
pattern.

To use standalone:

    use Nucleus\EventDispatcher\EventDispatcher;
    use Nucleus\IService\EventDispatcher\IEvent;

    $eventDispatcher = EventDispatcher::factory();

    $eventDispatcher->addListener(
        'Event.test',                    //This is the event name you want to listen
        function(IEvent $event) {        //This is any callable that will be callback when the event is dispatch
          //do whatever with the event
        }
    );

    $event = $eventDispatcher->dispatch(
        'Event.test',                    //The name of the event to dispatch
        $eventDispatcher,                //The subject of the event, can be anything even null
        array('foo'=>'bar')              //A list of parameters releated to the event
    );

The EventDispatcher use the "invoker" service to call the $callable. This mean 
that the parameters the parameters will be mapped by their name and the event
object and the subject will be mapped by their type.

    // ... //

    $eventDispatcher->addListener(
        'Event.test',                    
        function($foo,EventDispatcher $dispatcher) {       
          echo $foo; // will output: bar
          //$dispatcher is the event dispatcher set as the subject because the typping match
        }
    );

    $event = $eventDispatcher->dispatch(
        'Event.test',                
        $eventDispatcher,
        array('foo'=>'bar')
    );

With this technic you don't need to have different method for your listener,
the same method you are calling from another context can be call via a event
as long as the parameter match.