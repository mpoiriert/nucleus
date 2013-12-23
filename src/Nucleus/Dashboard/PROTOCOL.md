# Dashboard Protocol

The frontend and the backend of the dashboard communicate with a JSON-based protocol.
They exchange schema to describe actions.

## URLs

 - The frontend will first call `/_schema` on the base URL provided to get the full list of available actions
 - An action is defined as a URL endpoint (eg: `/user/create`).
 - The related action schema should be available by appending `/_schema` to the action's url (eg: `/user/create/_schema`)

## Root schema

The root schema contains a list of all available actions. It is a JSON array containing
objects will the following properties:

 - name: string, action name
 - controller: string, controller name
 - title: string, user displayed action name
 - menu: string, menu name of the action
 - icon: string, icon name
 - description: string
 - default: bool, whether this is the default action for the controller
 - url: string, url of the action

The *menu* property is a path-like string (forward slash separated) describing the action
position in the menu.

## Action schema

An action schema is a JSON object with the following properties:

 - name: string
 - title: string
 - icon: string
 - description: string
 - input: input schema
 - output: output schema

### Input schema

A JSON object with the following properties:

 - type: string
 - delegate: string, optional, if flow == delegate
 - url: string, url to invoke the action (unless flow == delegate)
 - behaviors: behaviors schema

If input type != none

 - model\_name: string
 - fields: fields schema

### Output schema

A JSON object with the following properties

 - type: string
 - flow: string
 - next\_action: string, optional, depends on the flow

Depending on the output type (other than none, redirect, file, dyamic and builder):

 - behaviors: behaviors schema

If it has a return model:

 - actions: array of model actions (action schema)
 - model\_name: string
 - model\_repr: string, name of the string repr field
 - fields: fields schema

### Behaviors schema

A JSON object where property names are behavior names and their value an object
containing the behavior's parameters. If the behavior is invokable, the url property
will contain the url to call.

### Fields schema

A JSON object with the following properties:

 - type: string
 - is\_array: bool
 - is\_hash: bool
 - field\_type: string, html field type
 - field\_options: JSON object
 - formated\_type: string
 - name: string
 - title: string
 - description: string
 - optional: bool
 - defaultValue: string
 - identifier: bool
 - visibility: array of strings
 - related\_model: null if none or an object with the following properties:
   - name: string
   - identifier: bool
   - repr: string
   - controller: string
   - actions: array of strings
   - embed: bool
   - fields: fields schema
 - value\_controller: null if none or an object with the following properties:
   - controller: string
   - remote\_id: string
   - local\_id: string
   - embed: bool
 - i18n: an array of strings

## Invoking an action

Perform a GET or POST request on the action's url. If it is a GET request, parameters
will be taken from the query string only.

If it is a POST request, the backend expects a *data* parameter in the POST data which
must be a JSON encoded string. The data should be an object with properly typed values.

File uploads will be handled.

## Response format

The response is wrapper in a JSON object with the following properties:

 - In case of success:
   - success: bool, true
   - data: mixed
 - In case of error:
   - success: bool, false
   - message: string

In case of a successful response, the data is expected to match a format depending on
the output type.

 - list: an array of objects
 - form or object: an object
 - dynamic: an object with the following properties:
   - schema: the new output schema
   - data: response data
 - builder: an object with the following properties:
   - controller: string
   - action: string
   - schema: action schema
   - data: response data
   - force\_input: bool

In the case of file output, the response will not send JSON back but a proper HTTP
file download response with the appropriate headers.