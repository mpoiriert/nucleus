Session
=======

The session are base on the HttpFoundation component of Symfony. So the interface

    Symfony\Component\HttpFoundation\Session\SessionInterface

must be implemented by the class service.

You can inject the session with the service name "session" if you want to use
it directly but the is a annotation @BoundToSession that you can put above
any properties (public, protected or private) this will inject the value if
present in the session and save it to session at the end of the call. If the
value is not present in the session the default value will be used and store
at the end of the call. This annotation just work on a class that is used as
a service, since the service name is used to escape the property name in the
session.

Here is a simple service that you can make to see.

    class MyBoundToSession
    {
        /**
         * @BoundToSession
         */
        private $value = 0;

        public function increment()
        {
          $this->value++;
          return $this->value;
        }
    }

If you want to access this value from another service you can do a getter method
on this service and inject it in the service that need to access the value. This
system make it really simple to save value to session and be sure that nobody
mess around with the value. If it can be access for read or write the complete
responsability is in the class that declared it.