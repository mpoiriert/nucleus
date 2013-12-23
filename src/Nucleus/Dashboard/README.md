# Dashboard

The Dashboard is dynamically generated using annotations on your classes.
There are two main types of "objects":

 - Controllers: they define actions
 - Models: they hold the data

## Controllers

Controllers are classes which methods can be defined as actions in the dashboard.
Actions can be added to the menu. The controller in itself has little meaning,
only its actions are determining.

To define a controller, use the `@\Nucleus\IService\Dashboard\Controller` annotation.
Methods which you want to use as actions should be marked with the `@\Nucleus\IService\Dashboard\Action`
annotation.

    <?php

    /**
     * @\Nucleus\IService\Dashboard\Controller
     */
    class MyController
    {
        /**
         * @\Nucleus\IService\Dashboard\Action
         */
        public function index()
        {
        }
    }

Each actions can receive input and has an output.
There are a few possible inputs which you can specify using the *in* property:

 - *none*: the output is immediatly displayed without any data
 - *call*: just calls the action without any arguments (default)
 - *form*: display a form to fill in the value of the arguments
 - *dynamic*: the method will receive an array of params as only argument

You can specify the type of output using the *out* property:

 - *none*: nothing happens
 - *list*: the action returns an array (or an iterator) of models which will be displayed in a table
 - *object*: the action returns a model which will be displayed
 - *form*: the action returns a model which will be displayed as a form
 - *redirect*: use in combination with a redirect flow to return which url to redirect to
 - *file*: returns a file to download (you have to return the file content or a tuple (filename, content))
 - *dynamic*: returns an ActionDefinition which will be used to display the output of the action (see Dynamism section)
 - *builder*: returns an ActionDefinition which will be used to create a new action in the dashboard (see Dynamism section)
 - *html*: returns an html string

The dashboard builder will make some asumptions to avoid having to describe
every bit of information.

 - A method with arguments will automatically use the "form" type of input unless specified
 - A method with an `@return` annotation will change the type of output:
   - If the return type ends with `[]` then the output type will be "list"
   - Otherwise it's "object"

Every objects returned by an action will be considered a model.

If no ̀@return` annotation are used and you set a custom output type, you can use the
*model* property of the Action annotation to define the returned model

    @Action(out="list", model="MyModel")

Actions will be visible in the menu unless specified otherwise. To hide an action
from the menu use `menu=false` in the annotation. The menu property can also be a string
which defines the parent menu item. By default it will be the controller's menu.
Multiple levels can be specified using slashes.

    @Action(menu=false)
    @Action(menu="Users")
    @Action(menu="Users/Admins")

Actions have a few other options:

 - *icon*: a font awesome icon name
 - *title*: the displayed name of the action

Example:

    <?php

    /**
     * @\Nucleus\IService\Dashboard\Controller
     */
    class MyController
    {
        /**
         * @\Nucleus\IService\Dashboard\Action
         * @return User[]
         */
        public function listUsers()
        {
            return array(
                new User('Paul'),
                new User('Peter')
            );
        }
    }

Tip: Actions can receive the Dashboard, Request or Response objects as parameters
respectively using typed parameters named $dashboard, $request or $response.

## Models

Models are classes which are data objects. Each model has a set of fields.
Fields can be define using annotations on properties or at the class level.
If the properties is not public, the relevant getter/setter will be used.

    <?php

    /**
     * @\Nucleus\IService\Dashboard\ModelField(property="dynamicfield")
     */
    class MyModel
    {
        /**
         * @\Nucleus\IService\Dashboard\ModelField(identifier=true)
         * @var int
         */
        public $id;

        /**
         * @\Nucleus\IService\Dashboard\ModelField()
         * @var string
         */
        public $name;
    }

Fields have a few options:

 - *name*: public name
 - *description*
 - *property*: name of the property when the field is defined at the class level
 - *type*: type of the property. `@var` may be used instead
 - *identifier*: whether the property is the model's identifier
 - *repr*: whether the field is used as the string representation of the model
 - *getter*: optional getter method name
 - *setter*: optional setter method name
 - *formField*: the HTML input type
 - *visibility*: an array of allowed visibility level (list, edit, view, query)
 - *required*: whether the property is required

Models can declare a loader: a callback which will be given the model's identifier
as argument and which should return a loaded model.

    <?php

    /**
     * @\Nucleus\IService\Dashboard\Model(loader="MyModel::find")
     */
    class MyModel
    {
        /**
         * @\Nucleus\IService\Dashboard\ModelField(identifier=true)
         * @var int
         */
        public $id;

        public static function find($id)
        {
            // ...
            return $model;
        }
    }

Alternatively, `@\Nucleus\IService\Dashboard\ModelLoader` can be used on a
method of the model class to use it as the model loader.

## Input validation

Models may be validated using Symfony's Validator component. Fields support
Validator's annotations but you may also use the `@\Nucleus\IService\Dashboard\Validate`
annotation.

    <?php

    class MyModel
    {
        /**
         * @\Nucleus\IService\Dashboard\ModelField(identifier=true)
         * @var int
         */
        public $id;

        /**
         * @\Nucleus\IService\Dashboard\ModelField()
         * @\Symfony\Component\Validator\Constraints\NotBlank
         * @var string
         */
        public $name;
    }

Or:

    /**
     * @\Nucleus\IService\Dashboard\ModelField()
     * @\Nucleus\IService\Dashboard\Validate(constraint='NotBlank')
     * @var string
     */
    public $name;

The Validate annotation can be used on actions to validate input parameters:

    /**
     * @\Nucleus\IService\Dashboard\Action()
     * @\Nucleus\IService\Dashboard\Validate(property='name', constraint='NotBlank')
     */
    public function add($name)
    {

    }


## Model actions

Models can define their own actions which will be available everytime they are displayed.
These actions are defined the same way as actions on controllers.

    <?php

    class MyModel
    {
        /**
         * @\Nucleus\IService\Dashboard\ModelField(identifier=true)
         * @var int
         */
        public $id;

        /**
         * @\Nulceus\IService\Dashboard\Action
         */
        public function delete()
        {
        }
    }

It is also possible to define model actions from controllers using the property *on\_model*
of the Action annotation. It must specify the class name of a model. These model actions will
only be available on model of the speicified type returned by the current controller

The action will receive the model identifier as argument.

    <?php

    /**
     * @\Nucleus\IService\Dashboard\Controller()
     */
    class MyController
    {
        /**
         * @\Nucleus\IService\Dashboard\Action
         * @return User[]
         */
        public function listUsers()
        {
            return array(
                new User('Paul'),
                new User('Peter')
            );
        }

        /**
         * @\Nucleus\IService\Dashboard\Action(on_model="User")
         */
        public function delete($id)
        {

        }
    }

## Security

The dashboard actions support the `@Nucleus\IService\Security\Secure` annotation.
Only actions which the current user is allowed to execute will be displayed.

## Behaviors

Actions can have associated behaviors to augment their output. Behaviors can
be added using the `@ActionBehavior` annotation. It takes as params:

 - *class*: class name of the behavior
 - *params*: a YAML hash of params

A behavior must extend the `Nucleus\Dashboard\ActionBehaviors\AbstractActionBehavior`.
Custom parameters can be defined in the `$parameters` property. Some special methods
can be added which will be called at certain point of the dashboard execution:

    - *beforeInvoke*: called before an action is invoked
    - *afterInvoke*: called after an action has been invoked but before the results are sent
    - *beforeInvokeModel*: called before a model action is invoked
    - *afterInvokeModel*: called after a model action is invoked
    - *formatInvokedResponse*: called after the response has been formated
    - *invoke*: special method that will allow the behavior to be called from the client side

## Pagination, sorting and filtering

When results are returned as a "list", the output may be paginated and/or sorted.

To enable pagination use the `@\Nucleus\IService\Dashboard\Paginate` annotation
with a few options:

 - *per\_page*: number of items per page (default: 20)
 - *offset\_param*: name of the action parameter which will be used to specify the current offset
 - *auto*: automatic pagination

Paginated actions (unless auto=true) must return a tuple (numberOfItems, items).

Automatic pagination will wrapped the returned array or iterator in a `LimitIterator`
and return only the current page's items.

Results may also be sorted using the `@\Nucleus\IService\Dashboard\Orderable`
annotation. Options:

 - *param*: name of the action parameter used to specify the field to sort (default: sort)
 - *order\_param*: name of the parameter to specify the sorting order: asc or desc

Results may be filtered using the `@\Nucleus\IService\Dashboard\Filterable` action.
It will provide to the action an array of field names / values to filter the output.

Example:

    /**
     * @\Nucleus\IService\Dashboard\Action()
     * @\Nucleus\IService\Dashboard\Paginate(per_page=10, offset_param='offset')
     * @\Nucleus\IService\Dashboard\Orderable(order_param='sortOrder')
     * @\Nucleus\IService\Dashboard\Filterable(param='filters')
     * @return User[]
     */
    public function listAll($filters, $offset, $sort, $sortOrder)
    {
        // ...
        return array($count, $items);
    }

## Propel bridge

CRUD controllers can be auto-generated for Propel models using the [Propel Bridge](Bridge/Propel/README.md)

## Defining controllers programmatically

Controllers and models can be defined programmatically. Controllers are described
using a `Nucleus\Dashboard\ControllerDefinition` object and models are described
using a `Nucleus\Dashboard\ModelDefinition` object.

Additionally, actions needs to be defined using `Nucleus\Dashboard\ActionDefinition`
and model fields using `Nucleus\Dashboard\FieldDefinition`.

All these classes use a fluent interface and can be constructed statically using
the `create()` method.

    $userModel = ModelDefinition::create()
        ->setClassName('User')
        ->addField(FieldDefinition::create()
            ->setProperty('FirstName')
            ->setType('string'))
        ->addField(FieldDefinition::create()
            ->setProperty('LastName')
            ->setType('string'));

    $userController = ControllerDefinition::create()
        ->setClassName('UserController')
        ->addAction(ActionDefinition::create()
            ->setName('listUsers')
            ->setReturnType('User[]')
            ->setReturnModel($userModel));

    $dashboard->addController($userController);

## Related models

Model's can be linked together but this can only be done programmatically (or with the
propel bridge).

The type of a model fields may be an object or an array of objects which are defined
by a model. This model is called a related model and can be defined using `setRelatedModel()`.

    $field->setRelatedModel($model);

The dashboard interface will display tabs in the edit and view panels which allow you
to access these related models.

Manipulating a related object can be done using another controller. For example, a related
model can have its own controller which you may want to use to modify these objects instead
of duplicating actions.

The value controller is defined using `setValueController()` which takes a controller name
as mandatory parameter.

## Dynamism with dashboard actions

Actions can be dynamic using the *dynamic* or *builder* return type. Note that dynamic input
is very easy to achieve with the *dynamic* input type.

The *dynamic* return type allows you to define a new output definition along with
some data. This new definition has to be defined using an ActionDefinition object.

    /**
     * @\Nucleus\IService\Dashboard\Action(out="dynamic")
     */
    public function dynamicAction()
    {
        $overrideOutput = ActionDefinition::create()->...;
        $data = array(...);
        return array($overrideOutput, $data);
    }

The *builder* return type allows you to create a whole new actions which input
will also be called. Your actions must return an array with the following properties:

 - *action*: ActionDefinition object for the new action
 - *controller*: ControllerDefinition object which the action belongs to (optional, default to current controller)
 - *data*: data for the new action (optional, will skip input)
 - *force\_input*: whether input should be forced when data is specified (bool, default false)

An example usage of the builder output is to create an action with a dynamic input,
the form fields being created depending on parameters. Example:

    /**
     * @\Nucleus\IService\Dashboard\Action(out="builder")
     */
    public function processVideos($id, Dashboard $dashboard)
    {
        $action = $dashboard->getController('...')->getAction('processVideosForm');
        // replace the input model:
        $newInputModel = ModelDefinition::create()->addField(/* ... */);
        $action->setInputType('form')->setInputModel($newInputModel);
        return $action; // you can also only return an ActionDefinition object
    }

    /**
     * @\Nucleus\IService\Dashboard\Action(in="dynamic")
     */
    public function processVideosForm($params)
    {
        // process fields form $params array
    }


## Dashboard schema

The dashboard will generate a schema from the annotations which will be used to
generate the frontend interface. The schema is a JSON blob listing all available
actions.

Each action has its own schema available by appending /\_schema at the end of the
action URL.

[Protocol definition](PROTOCOL.md)