# Propel bridge

There are two Propel behaviors to help integrate Propel models in the dashboard:

 - *dashboard\_model*: creates a `ModelDefinition` object for the Propel model
 - *dashboard\_controller*: create a controller to perform CRUD operation on the model in the dashboard

If *dashboard\_controller* is used on a model without *dashboard\_model*, the latter will
be automatically activated.

Tip: use *dashboard\_controller* as a global behavior to create a controller for every models

## Models

The following features of Propel models will be available through the model definition:

 - validators
 - loading using a query object
 - types
 - primary key as identifier
 - use the model's getters/setters
 - the delete action
 - default value

The following parameters are available:

 - *include* or *exclude*: list of field names, include or exclude fields
 - *delete\_action*: boolean, whether to add the delete action
 - *namealiases*: hash (name:alias), aliases of property names
 - *htmlfields*: hash (name:type), override html input type
 - *repr*: string, name of the field to be used as the string repr
 - *nolist* or *listable*: list of field names which can be displayed in a list
 - *noedit* or *editable*: list of field names which can be displayed in a form
 - *noview* or *viewable*: list of field names which can be displayed in an object view
 - *noquery* or *queryable*: list of field names which can be queried (for filters)
 - *children*: list of table names with a one-to-many relationship to the current table which can be used as related objects
 - *nocreatechildren*: list of child table names where related objects cannot be created
 - *noremovechildren*: list of child table names where related objects cannot be removed
 - *noeditchildren*: list of child table names where related objects cannot be edited
 - *noviewchildren*: list of child table names where related objects cannot be viewed
 - *childaliases*: hash (table:alias), aliases for children
 - *internal*: list of field names, defines internal fields
 - *propertyaliases*: hash (field:alias), aliases for properties
 - *fkembed*: list of field names, whether to embed related models (one-to-one relationships)
 - *novcembed*: list of field names, whether to make the related model selectable from a select box
 - *typeoverrides*: hash (field:type), override type of fields
 - *hide\_rank\_column*: boolean, hide the rank column when using the sortable behavior

List items are separated by commas. Hash items are separated by commas and hash the value is
separated from the key with a ':'.

    <behavior name="dashboard_model">
        <parameter name="exclude" value="field1,field2" />
        <parameter name="htmlfields" value="description:textarea,long_desc:richtext" />
    </behavior>

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
 - *menu*: default menu for actions of this controller
 - *autolist*: set to true to make list load only on-demand
 - *edit*: set to false to make this model read-only