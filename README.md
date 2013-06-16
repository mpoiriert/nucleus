Nucleus
========

Small learning curve PHP framework for professional application development.

[![Build Status](https://api.travis-ci.org/mpoiriert/nucleus.png?branch=master)](http://travis-ci.org/mpoiriert/nucleus)

Nucleus want to be a independant standalone framework, but also, with the
help of adapter, being use in any other PHP framework. Using dependency injection,
it's mainly use a json configuration file for it's initialization (by default nucleus.json).
So any project base on nucleus should ahve a nucleus.json file at it's root.

nucleus.json
-------------

Whitin the file you will find the configuration for any services and also a
imports section if you need to import other file. The concept is base on the
configuration system of the Symfony project. The main difference is that the
system only support json file and rely mainly on annotations for all the other
"configuration".

Here is a exemple of a configuration file:

    {
        "imports": [
            "/src/Nucleus/Framework/nucleus.json",
            "/src/Nucleus/Dashboard/nucleus.json"
        ],
         "services": {
            "customService": {
                "class": "My\\Namespace\\CustomService"
            },
            "assetManager": {
                "configuration": {
                    "rootDirectory": "<?php echo str_replace('\\','/',__DIR__) . '/web' ?>"
                }
            }
         }
    }

Has you may notice there is a php tag (<?php ... ?>) in the file, so the php
is interpreted while parsing the json file.

From what you can see of the file, it does need 2 otherfiles to work:

  * /src/Nucleus/Framework/nucleus.json
  * /src/Nucleus/Dashboard/nucleus.json

In the Framework/nucleus.json you will find all the other configuration of all
the services need for the framework to work. All the services have their own
configuration file so you might build your own custom configuration file without
the loading everything, will talk about that more later. And you can also see
the Dashboard/nucleus.json file, this file is more the custom service for this 
application, like a third party would have done. **Note that the file path specified
in the import can be relative to the file itself**.

Whitin the file you can see the is a definition of a service named "customService"
and the class that it will use for the instanciation of this service.

Also under it you have the "assetManager" but without a "class" attribute specified.
In this specific case if you would load the other imported files you will find
at some point the "class" attribute for this service. We did override the 
"configuration" -> "rootDirectory" value of the asset manager in this specific
file instead of using the default value.

Loading a configuration
-----------------------

To load a configuration you must use a static method in the Nucleus\Framework\Nucleus class.

    $nucleus = \Nucleus\Framework\Nucleus::factory('/path/to/your/config/file/nucleus.json')

This will return a complete Nucleus\Framework\Nucleus object, but from there 
the only method available is "getServiceContainer". You also have

    // ... //
    $service = \Nucleus\Framework\Nucleus::serviceFactory('/path/to/your/config/file/nucleus.json',$serviceName):
    // ... //

This will return only the service specified. You will lose the direct any reference 
to the service container and any other services that might have been initialized 
(unless the service you are requesting needed the serviceContainer itself and
have a method to access it). This exemple is the proper way to initialize a service 
that you want to use as a standalone object in another project without knowing how
to instanciate it.

Also as a shortcut and if you don't need to override any default value, some of the
service implemented in Nucleus have a "factory" method. If we use the IEventDispatcher
implentation as a exemple:

    $eventDispatcher = \Nucleus\EventDispatcher\EventDipatcher::factory();

This will instanciate a Nucleus\EventDispatcher\EventDispatcher with the default
configuration specified in the "nucleus.json" file within the Nucleus\EventDispatcher
namespace folder.

\Nucleus\IService namespaces
-----------------------------

There is a generic namespace that is use to define services interfaces. You
should always refer to those interface within your service instead of specific
class. This will allow you to use third party implementation of the services
and also build your own implementation with your specific need and being compatible
with other library. Other than services interface you might also find Exception
class so the Exception thrown by your implementation will respect the defined 
convention.

To help you with building your own service implementation, unit test 
(base on PHPUnit) can be found in the specific service folder. The "PHPUnit_Framework_TestCase"
are abstract and normally need you to implement a getter method specific to the
service you want to test. That way you don't need to write a full unit test
if you want to do your own implementation of a service. You might need to test
your own custom method, but the generic service interface will be fully tested
from the abstract test. **Even if we refer it at Unit Test, consider them as
integration test since we are trying to use the less amount of mock object possible**

Service you should know about
-----------------------------

Obviously I recommended you to check all the services available and their own
documentation, but the one I recommend particulary are:

  * DependencyInjection
  * EventDispatcher
  * Routing
  * FrontController
  * View

Making your own small Rest application
--------------------------------------

Now that you have a basic understanding of the concept (or you don't but need
exemple to understand). Let say that you want to do small webservice api you'll 
need a nucleus.json file who look like this:

    {
      "imports": [
        "vendor/mpoiriert/nucleus/src/Nucleus/DependencyInjection/nucleus.json",
        "vendor/mpoiriert/nucleus/src/Nucleus/Routing/nucleus.json",
        "vendor/mpoiriert/nucleus/src/Nucleus/FrontController/nucleus.json"
      ],
      "services": {
        "myApi": {
          "class": "My\\Api"
        }
      }
    }

The class My\Api will look like this (obviously you will want to do
more and usefull method in your api):

    namespace My;

    class Api 
    {
      /**
       * @Route(name="getTime", path="/myApi/time")
       */
      public function getTime()
      {
        return time();
      }
    }

If you are using an app or browser that have a accept application/json header
it will render accordingly. **I'm using firefox with the JsonView add-on and also
edit the accept header via the about:config adress of firefox to add
application/json in the list** 

In the root file of your application (you might want to use a modrewrite for that)
you must put:

    <?php

    require_once(__DIR__ . '/../vendor/autoload.php');

    $request = Symfony\Component\HttpFoundation\Request::createFromGlobals();

    \Nucleus\Framework\Nucleus::serviceFactory(
      __DIR__ . '/../nucleus.json',              //The path to you json file
      "frontController"
    )->handleRequest($request);

As you see the system is base on composer and is also using the symfony/httpfoundation
component for it's request.

You should get a answer like this (result being the default root attribute):

    //url   http://domain.com/myApi/time
    {"result":1369244261}

If your Api need another service to (let say you have a clock service), you
can add the service in the configuration file and inject it to your api:

    // nucleus.json
    {
      /* ... */
      "services": {
        /* ... */
        "myClock": {
          "class": "My\\Clock"
        }
      }
    }

    // My\Clock class

    namespace My;

    class Clock 
    {
      public function getTime()
      {
        return time();
      }
    }

    // My\Api class with the clock injection

    namespace My;

    class Api 
    {
      private $clock;
      /**
       * @Inject
       */
      public function initiliaze(Clock $myClock)
      {
        $this->clock = $myClock;
      }

      /**
       * @Route(name="getTime", path="/myApi/time")
       */
      public function getTime()
      {
        return $this->clock->getTime();
      }
    }

If you didn't read the documentation of the **Service you should know about** section
you might not understand everything, this is a good time to go take a look to it.
