# Dashboard Frontend

The Dashboard's frontend is a Backbone.js application. The application should be started
using `Dashboard.start()` in *app.js*:

    Dashboard.start({
        base_url: "/"
        // other config keys
    });

The main application object is of type `Dashboard.App` and the current instance available
at `Dashboard.app`. Urls are managed using a router of type `Dashboard.Router` and the
current instance is available at `Dashboard.router`.

`Dashboard.api` exposes a `Dashboard.Utils.RestEndpoint` object to query the backend.

The application object dispatches actions. An action in the frontend is matched to a
controller's action in the backend. Actions are represented by `Dashboard.Action` object
which allow to query an action:

    var action = new Dashboard.Action("mycontroller", "myaction");
    action.on('input', handleInputCallback);
    action.on('response', handleResponseCallback);
    action.execute();

An action view is represented by a `Dashboard.ActionView` object. The full dispatch process
of an action can be triggered from the application using `runAction()`:

    Dashboard.app.runAction("mycontroller", "myaction");

An ActionView listends to the Action object events and displays the related interface.
Interface elements are called widgets and are located in the widgets.\*.js files.

When an action requires input, a `Dashboard.Widgets.FormView` object will be used.
Depending on the type of output, the correct wigdet will be used.