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
     * @\Nucleus\IService\Dashboard\Controller()
     */
    class MyController
    {
        /**
         * @\Nucleus\IService\Dashboard\Action()
         */
        public function index()
        {
        }
    }

Each actions can receive input and has an output.
There are a two possible inputs which you can specify using the *in* properties:

 - *call*: just calls the action without any arguments
 - *form*: display a form to fill in the value of the arguments

There are four kinds of output which you can specify using the *out* properties:

 - *none*: nothing happends
 - *object*: the action returns a model which will be displayed
 - *list*: the action returns an array (or an iterator) of models which will be displayed in a table
 - *form*: the action returns a model which will be displayed as a form

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
which defines the parent menu item. By default it will be the controller's title.
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
     * @\Nucleus\IService\Dashboard\Controller()
     */
    class MyController
    {
        /**
         * @\Nucleus\IService\Dashboard\Action()
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

## Models

Models are classes which are data objects. Each models as a set of fields.
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
 - *formField*: the HTML input type
 - *listable*: whether the property is listable
 - *editable*: whether the property is editable
 - *link*: an action name which will be triggered when the property is clicked
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

Alternatively, the `@\Nucleus\IService\Dashboard\ModelLoader` can be used on a
method of the model class to use it as the model loader.

## Input validation

Models may be validated using Symfony's Validator component. Fiels support
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
         * @\Nulceus\IService\Dashboard\Action()
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
         * @\Nucleus\IService\Dashboard\Action()
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

## Pagination and sorting

When results are returned as a "list", the output may be paginated and/or sorted.

To enable pagination use the `@\Nucleus\IService\Dashboard\Paginate` annotation
with a few options:

 - *per\_page*: number of items per page (default: 20)
 - *offset\_param*: name of the action parameter which will be used to specify the current offset
 - *auto*: automatic pagination

Paginated actions (unless auto=true) must return a tuple (numberOfItems, items).

Automatic pagination will wrapped the returned array or iterator in a `LimitIterator`
and return only the current page's items.

Results may also be sortable using the `@\Nucleus\IService\Dashboard\Sortable`
annotation. Options:

 - *param*: name of the action parameter used to specify the field to sort (default: sort)
 - *order\_param*: name of the parameter to specify the sorting order: asc or desc

Example:

    /**
     * @\Nucleus\IService\Dashboard\Action()
     * @\Nucleus\IService\Dashboard\Paginate(per_page=10, offset_param='offset')
     * @\Nucleus\IService\Dashboard\Sortable(order_param='sortOrder')
     * @return User[]
     */
    public function listAll($offset, $sort, $sortOrder)
    {
        // ...
        return array($count, $items);
    }

## Dashboard schema

The dashboard will generate a schema from the annotations which will be used to
generate the frontend interface. The schema is a JSON blob listing all available
actions.

Each action has its own schema available by appending /\_schema at the end of the
action URL.

