# Propel bridge

There are two Propel behaviors to help integrate Propel models in the dashboard:

 - *dashboard\_model*: creates a `ModelDefinition` object for the Propel model
 - *dashboard\_controller*: create a controller to perform CRUD operation on the model in the dashboard

If *dashboard\_controller* is used on a model without *dashboard\_model*, the latter will
be automatically activated.

Tip: use *dashboard\_controller* as a global behavior to create a controller for every models

## Models

The following features of Propel's models will be available through the model definition:

 - validators
 - loading using a query object
 - types
 - primary key as identifier
 - use the model's getters/setters
 - the delete action
 - default value

You can include/exclude columns from the `ModelDefinition` using either the *exclude*
or *include* parameters:

    <behavior name="dashboard_model">
        <parameter name="exclude" name="col1,col2" />
    </behavior>

Tip: use a comma-separated list of columns to include/exclude many columns

The `ModelDefinition` object is available throught the `getDashboardModelDefinition()` static
method of your model object.

## Controllers

The *dashboard\_controller* behavior creates a basic CRUD controller with the following actions:

 - listAll: with support for pagination and ordering
 - add: create a new model
 - edit: edit an existing model

The *listAll* action supports pagination, ordering and filtering. If the *sortable* behavior
is activated on the table, sorting will be activated.

The controller is divided in two parts: a BaseController and a Controller. You can freely
override the latter.

The following parameters are available:

 - *items\_per\_page*: number of items per page in the pagination
 - *credentials*: activate the `@Secure` annotation with the provided permissions