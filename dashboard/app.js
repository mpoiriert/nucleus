var Dashboard = {};

$(function() {

    Dashboard.config = {};
    Dashboard.ensure_config = function(key) {
        if (typeof(Dashboard.config[key]) == 'undefined') {
            alert('Missing config key: ' + key);
            throw new Exception('Missing config key: ' + key);
        }
    }

    // ----------------------------------------------------

    /*
     * Class to easily call a rest endpoint
     */
    var RestEndpoint= function(base_url) {
        this.base_url = base_url;
        this.cache = {};
    };

    RestEndpoint.prototype.call = function(method, action, params, callback, err_callback) {
        if (typeof(action) == 'function') {
            action = action();
        }
        if (typeof(params) == 'function') {
            callback = params;
            params = {};
        }
        var options = {};
        if (typeof(action) != 'string') {
            _.extend(options, action);
            action = action.url;
        }

        if (options.cached && this.cache[options.cache_id]) {
            var data = this.cache[options.cache_id];
            if (data.success) {
                callback && callback(data.data);
            } else if (err_callback) {
                err_callback && err_callback(data.message);
            }
            return;
        }

        var payload = params;
        if (method === 'post') {
            payload = { data: JSON.stringify(params) };
        }

        $.ajax({
            url: this.base_url + action,
            type: method,
            dataType: 'json',
            crossDomain: true,
            data: payload
        }).done(_.bind(function(data) {
            this.cache[options.cache_id] = data.result;
            if (data.result.success) {
                callback && callback(data.result.data);
            } else if (err_callback) {
                err_callback && err_callback(data.result.message);
            }
        }, this))
        .fail(function(xhr, status) {
            err_callback && err_callback(status);
        });
    };

    RestEndpoint.prototype.clearCache = function() {
        this.cache = {};
    };

    RestEndpoint.prototype.createEndpoint = function(url) {
        return new RestEndpoint(this.base_url + url);
    };

    _(['get', 'post', 'put', 'delete']).each(function(method) {
        RestEndpoint.prototype[method] = function(action, params, callback) {
            return this.call(method.toUpperCase(), action, params, callback);
        };
    });

    RestEndpoint.cached = function(url) {
        return {url: url, cached: true, cache_id: url};
    };

    // ----------------------------------------------------
    
    var render_template = function(id, data) {
        var tpl = _.template($(id).html());
        return tpl(_.extend({
            render_template: render_template
        }, data));
    };

    var serialize = function(container) {
        var ignoreEmptyValues = arguments.length > 1 ? arguments[1] : false;
        var o = {};
        var map = {
            "int": function(v) { return parseInt(v, 10); },
            "double": parseFloat,
            "float": parseFloat
        };
        $(container).find(':input:not(button)').each(function() {
            var $this = $(this);

            if ($this.hasClass('no-serialize') || (this.type == 'radio' && !this.checked)) {
                return;
            }

            var v = this.value || '';
            var t = $this.data('type') || 'string';
            var is_array = false;
            var localized = $this.hasClass('localized');

            if (!v && ignoreEmptyValues) {
                return;
            }

            if (localized) {
                v = $this.data('localized');
            } else if (is_array) {
                v = v.split(',');
            } else if (this.type == 'checkbox' && (t == 'bool' || t == 'boolean')) {
                if (!this.checked && ignoreEmptyValues) {
                    return;
                }
                v = this.checked;
            }

            if (t.indexOf('[]') > -1) {
                t = t.substr(0, t.length - 2);
                is_array = true;
            }

            var cast = map[t] !== undefined ? map[t] : function(v) { return v; };

            if (is_array) {
                v = _.map(v, cast);
            } else if (!localized) {
                v = cast(v);
            }

            o[this.name] = v;
        });
        return o;
    };

    var get_identifiers = function(fields) {
        var idfields = [];
        for (var i = 0; i < fields.length; i++) {
            if (fields[i].identifier) {
                idfields.push(fields[i])
            }
        }
        return idfields;
    };

    var build_pk = function(fields, data) {
        var pk = {};
        for (var i = 0; i < fields.length; i++) {
            if (fields[i].identifier && data[fields[i].name]) {
                pk[fields[i].name] = data[fields[i].name];
            }
        }
        return pk;
    };

    var get_field = function(fields, name) {
        for (var i = 0; i < fields.length; i++) {
            if (fields[i].name == name) {
                return fields[i];
            }
        }
        return null;
    };

    var filter_visible_fields = function(fields, visibility) {
        if (!_.isArray(visibility)) {
            visibility = [visibility];
        }
        var vfields = [];
        for (var i = 0; i < fields.length; i++) {
            if (_.intersection(fields[i].visibility, visibility).length > 0) {
                vfields.push(fields[i])
            }
        }
        return vfields;
    };


    var format_value = function(v, f) {
        if (f && f.i18n) {
            v = v[f.i18n[0]];
        }
        if (v === false) {
            return 'false';
        } else if (v === true) {
            return 'true';
        }
        return v || '';
    };

    var render_table = function(fields, rows, with_value_controller) {
        var vfields = filter_visible_fields(fields, 'list');
        var table = $(render_template('#table-tpl', { fields: vfields }));
        var tbody = '';

        _.each(rows, function(row, index) {
            tbody += '<tr><td align="center"><input type="radio" name="id" value=\'' + JSON.stringify(build_pk(fields, row)) + '\'></td>';

            _.each(vfields, function(f) {
                tbody += '<td>';
                if (with_value_controller && f.value_controller) {
                    var url = Dashboard.config.base_url + "/" + f.value_controller.controller + "/edit?" + f.value_controller.remote_id + '=' + row[f.name];
                    tbody += '<a href="' + url + '" class="related" data-model="' + f.related_model.name + '" data-controller="' + f.value_controller.controller + '" ' +
                             'data-action="edit" data-params=\'{"' + f.value_controller.remote_id + '": "' + row[f.name] + '"}\'>' + 
                             format_value(row[f.name], f) + '</a>';
                } else {
                    tbody += format_value(row[f.name], f);
                }
                tbody += '</td>';
            });

            tbody += '</tr>';
        });

        table.find('tbody').html(tbody);
        table.find('tbody tr').on('click', function() {
            $(this).find('input[type="radio"]')[0].checked = true;
        });

        return table;
    };

    var serialize_table = function(table) {
        var data = JSON.parse(table.find('tbody input:checked').val());
        return data;
    };

    var create_modal = function(title, view) {
        var modal = $(render_template('#modal-tpl', { title: title })).appendTo('body');
        modal.find('.modal-body').append(view.el);
        modal.modal({ show: false });
        return modal;
    };

    var connect_action_to_view = function(action, view) {
        action.on('response', function() {
            view.unfreeze();
        });
        action.on('error', view.showError);
        view.on('submit', function(data) {
            view.freeze();
            action.execute(data);
        });
    };

    // ----------------------------------------------------

    /*
     * Represents a toolbar
     */
    Dashboard.ToolbarView = Backbone.View.extend({
        tagName: 'div',
        className: 'btn-toolbar',
        events: {
            "click a.btn": "btnClick"
        },
        options: {
            current_action: null
        },
        initialize: function() {
            this.base_url = this.options.base_url || '';
        },
        render: function() {
            this.$el.html(render_template('#toolbar-tpl', { 
                base_url: this.base_url, 
                buttons: this.options.buttons,
                current_action: this.options.current_action
            }));
            return this;
        },
        btnClick: function(e) {
            e.preventDefault();
            var a = $(e.currentTarget);
            if (a.hasClass('disabled')) {
                return;
            }
            this.trigger("btn-click", a.data('controller'), a.data('action'));
        },
        disable: function() {
            this.$('.btn').addClass('disabled');
        },
        enable: function() {
            this.$('.btn').removeClass('disabled');
        },
        buttons: function() {
            return this.$('a.btn');
        }
    });

    /*
     * Base view class for actions
     */
    Dashboard.BaseWidgetView = Backbone.View.extend({
        tagName: 'form',
        events: {
            "submit": "handleFormSubmited"
        },
        options: {
            doneOnEmptyData: true,
            refreshable: true
        },
        initialize: function() {
            this.$body = this.$el;
            this.overrideRequestData = {};
        },
        refresh: function() {
            if (!this.options.refreshable) {
                return;
            }
            this.freeze();
            this.parent.action._call(
                _.extend({}, this.parent.action.data, this.overrideRequestData), 
                _.bind(function(resp) {
                    if (!resp && this.options.doneOnEmptyData) {
                        this.trigger('done');
                        return;
                    }
                    this.model = resp;
                    this.unfreeze();
                    this.render();
                }, this),
                _.bind(function(message) {
                    this.unfreeze();
                    this.showError(message);
                }, this)
            );
        },
        renderTitle: function(title) {
            this.$body.append('<div class="page-header"><h3>' + title + '</h3></div>');
            return this;
        },
        renderTitleWithIdentifier: function(title) {
            var values = this.model || {};
            for (var i = 0; i < this.options.fields.length; i++) {
                if (this.options.fields[i].identifier) {
                    if (values[this.options.fields[i].name]) {
                        title += ' #' + values[this.options.fields[i].name];
                    }
                }
            }
            return this.renderTitle(title);
        },
        renderToolbar: function() {
            if (this.options.actions) {
                this.$toolbar = new Dashboard.ToolbarView({ base_url: "#", 
                        buttons: this.options.actions, current_action: this.parent.action.name });
                this.listenTo(this.$toolbar, 'btn-click', this.toolbarClick);
                this.$body.append(this.$toolbar.render().el);
            }
            return this.$body;
        },
        addSidebar: function() {
            if (this.$sidebar) {
                return;
            }

            var row = $('<div class="row-fluid" />'),
                body = $('<div class="span9" />').appendTo(row);
            
            this.$sidebar = $('<div class="span3 sidebar" />').appendTo(row);
            body.append(this.$body.children());
            this.$el.empty().append(row);
            this.$body = body;
        },
        toolbarClick: function(controller, action) {
            this.trigger('tbclick', controller, action, this.serialize());
        },
        handleFormSubmited: function(e) {
            this.trigger('submit', this.serialize());
            e.preventDefault();
        },
        freeze: function() {
            this.$(':input').attr('disabled', 'disabled');
        },
        unfreeze: function() {
            this.$(':input').removeAttr('disabled');
        },
        showError: function(message) {
            this.unfreeze();
            if (typeof(this.$error) === 'undefined') {
                this.$error = $('<div class="alert alert-error" />');
                if (this.$toolbar) {
                    this.$error.insertAfter(this.$toolbar.$el);
                } else {
                    this.$error.prependTo(this.$el);
                }
            }
            this.$error.text(message);
        },
        serialize: function() {
            return serialize(this.$body);
        }
    });

    /**
     * Represents a view to manage related models
     */
    Dashboard.RelatedModelsView = Dashboard.BaseWidgetView.extend({
        tagName: 'div',
        className: 'related-models-view',
        initialize: function() {
            Dashboard.RelatedModelsView.__super__.initialize.apply(this);
        },
        render: function() {
            this.$el.html('<em>Loading...</em>');
            this.refresh();
            return this;
        },
        refresh: function() {
            var action = new Dashboard.Action(this.options.controller, 'list' + this.options.name);
            this.freeze();
            this.listenTo(action, 'response', this.renderTable);
            action.execute(this.options.query_data)
        },
        renderTable: function(data) {
            var table = render_table(_.filter(this.options.model.fields, _.bind(function(f) {
                return f.name != this.options.remote_id; }, this)), data);

            var buttons = [];
            if (_.contains(this.options.model.actions, 'remove')) {
                buttons.push({
                    name: 'remove', 
                    controller: this.options.controller,
                    title: 'Remove',
                    icon: 'trash'
                });
            }
            if (_.contains(this.options.model.actions, 'create')) {
                buttons.push({
                    name: 'create',
                    controller: this.options.controller,
                    title: 'Add New',
                    icon: 'plus'
                });
            }
            if (_.contains(this.options.model.actions, 'add')) {
                buttons.push({
                    name: 'add',
                    controller: this.options.controller,
                    title: 'Add Existing',
                    icon: 'plus'
                });
            }

            var tb = new Dashboard.ToolbarView({ buttons: buttons});

            this.$el.empty().append(table).append(tb.render().el);
            this.unfreeze();

            tb.buttons().first().addClass('disabled');
            table.find('tbody tr').on('click', function() {
                tb.buttons().first().removeClass('disabled');
            });

            this.listenTo(tb, 'btn-click', function(controller, action) {
                if (action == 'remove') {
                    this.executeRemove(serialize_table(table));
                } else if (action == 'add') {
                    this.renderAdd();
                } else if (action == 'create') {
                    this.renderCreate();
                }
            });
        },
        executeRemove: function(id) {
            var action = new Dashboard.Action(this.options.controller, 'remove' + this.options.name);
            this.freeze();
            this.listenTo(action, 'response', this.refresh);
            action.execute(_.extend({}, this.options.query_data, id));
        },
        renderCreate: function() {
            var action = new Dashboard.Action(this.options.model.controller, 'add', null, false);
            var view = new Dashboard.FormWidgetView({
                tabs_for_related_models: false,
                action_title: 'Create',
                show_title: false,
                model_name: this.options.model.name,
                fields: this.options.model.fields,
                hidden_fields: [this.options.remote_id],
                field_visibility: ['edit'],
                model: this.options.query_data
            });
            var modal = create_modal(view.computeTitle(), view.render());

            connect_action_to_view(action, view);

            this.listenTo(action, 'response', function(data) {
                modal.modal('hide').remove();
                this.refresh();
            });
            
            modal.modal('show');
        },
        renderAdd: function() {
            var viewAction = new Dashboard.Action(this.options.model.controller, 'view', null, false);
            var addAction = new Dashboard.Action(this.options.controller, 'add' + this.options.name);
            var view = new Dashboard.FormWidgetView({
                tabs_for_related_models: false,
                action_title: 'Add',
                show_title: false,
                model_name: this.options.model.name,
                force_edit: true,
                fields: _.filter(this.options.model.fields, _.bind(function(f) {
                    return f.name == this.options.local_id; }, this))
            });
            var modal = create_modal(view.computeTitle(), view.render());

            connect_action_to_view(viewAction, view);
            addAction.on('error', view.showError);

            this.listenTo(viewAction, 'response', function(data) {
                addAction.execute(_.extend({}, data, this.options.query_data));
            });

            this.listenTo(addAction, 'response', function(data) {
                modal.modal('hide').remove();
                this.refresh();
            });
            
            modal.modal('show');
        }
    });

    /*
     * Represents an action to show a form
     */
    Dashboard.FormWidgetView = Dashboard.BaseWidgetView.extend({
        className: 'form-action-view form-horizontal',
        options: {
            title: null,
            show_title: true,
            tabs_for_related_models: true,
            field_visibility: ['edit', 'view'],
            hidden_fields: [],
            force_edit: false
        },
        computeTitle: function() {
            var title = '';
            if (this.options.action_title) {
                title = this.options.action_title + ' ';
            } else if (this.parent && this.parent.action) {
                title = this.parent.action.schema.title + ' ';
            }
            title += this.options.model_name;
            return title;
        },
        render: function() {
            var tabs = [];

            this.$el.empty();
            if (this.options.show_title) {
                this.renderTitleWithIdentifier(this.options.title || this.computeTitle());
            }
            this.renderToolbar();

            if (this.options.tabs_for_related_models) {
                tabs.push(this.options.model_name);
                for (var i = 0; i < this.options.fields.length; i++) {
                    if (this.options.fields[i].value_controller && this.options.fields[i].is_array) {
                        tabs.push(this.options.fields[i].name);
                    }
                }
            }

            if (tabs.length > 1) {
                this.$el.append(render_template('#tabs-tpl', { tabs: tabs }));
                this.renderForm(this.$('.tab-pane[id="tab-0"]'));
                this.$('.nav-tabs a[href="#tab-0"]').parent().addClass('active');
                this.renderTabs();
            } else {
                this.renderForm(this.$el);
            }

            return this;
        },
        renderForm: function(parent) {
            var values = this.model || {};
            var fields = filter_visible_fields(this.options.fields, this.options.field_visibility);

            fields = _.filter(fields, function(f) { return !f.value_controller || !f.is_array; });

            this.renderFields(parent, fields, values);
            this.renderFormButtons(parent);
        },
        renderFormButtons: function(parent) {
            parent.append('<div class="form-actions"><button type="submit" class="btn btn-primary">Submit</button></div>');
        },
        renderFields: function(form, fields, model) {
            var self = this;
            _.each(fields, function(field) {
                var value = typeof(model[field.name]) == 'undefined' ? field.defaultValue : model[field.name];
                if (!_.contains(self.options.hidden_fields, field.name)) {
                    form.append(self.renderField(field, value));
                } else {
                    form.append(self.renderHiddenField(field, value));
                }
            });
        },
        renderField: function(field, value) {
            if (!this.options.force_edit && !_.contains(field.visibility, 'edit')) {
                return this.wrapInBootstrapControlGroup(field.title, [
                    this.renderHiddenField(field, value), 
                    $('<span class="value" />').text(value)
                ]);
            }
            return this.renderEditableField(field, value);
        },
        renderEditableField: function(field, value) {
            var input;
            if (field.value_controller && !field.is_array) {
                input = this.renderValueControllerSelectBox(field, value);
            } else if (field.field_type == 'checkbox' || field.field_type == 'radio') {
                input = this.renderBooleanField(field, value);
            } else {
                input = this.renderInputField(field, value);
            }

            var inputs = [input];
            if (field.i18n) {
                this.makeFieldLocalizable(inputs, input, field, value);
            }

            return this.wrapInBootstrapControlGroup(field.title, inputs);
        },
        renderHiddenField: function(field, value) {
            return $('<input />').attr({ type: 'hidden', name: field.name }).val(value);
        },
        renderValueControllerSelectBox: function(field, value) {
            var select = this.createInputWithAttrs('select', field).addClass('related');

            var url = Dashboard.config.base_url + "/" + field.value_controller.controller  + "/listAll?__offset=-1";
            var m = field.related_model;

            select.append('<option>Loading...</option>');
            select[0].disabled = true;

            Dashboard.api.call('get',  url, function(resp) {
                select.empty();
                for (var i = 0; i < resp.data.length; i++) {
                    var s = value == resp.data[i][m.identifier[0]] ? 'selected="selected"' : '';
                    select.append('<option value="' + resp.data[i][m.identifier[0]] + '" ' + s + '>' + resp.data[i][m.repr] + '</option>');
                }
                select[0].disabled = false;
            });

            return select;
        },
        renderBooleanField: function(field, value) {
            var input = this.createInputWithAttrs('input', field)
                .attr('type', field.field_type)
                .val('1');

            if (value) {
                input[0].checked = true;
            }

            return input;
        },
        renderInputField: function(field, value) {
            var tagName = field.field_type == 'textarea' ? 'textarea' : 'input';
            var input = this.createInputWithAttrs(tagName, field).val(value);
            if (field.field_type != 'textarea') {
                if (!_.contains(['text', 'password'], field.field_type)) {
                    input.attr('type', 'text');
                    input[field.field_type](field.field_options);
                } else {
                    input.attr('type', field.field_type);
                }
            }
            return input;
        },
        makeFieldLocalizable: function(inputs, input, field, values) {
            var select = this.renderLocaleSelectionBox(field);
            inputs.unshift(select);

            values = values || {};

            select.data('target', input).on('change', function() {
                input.val(values[this.value] || '');
                $('select.localizer').each(function() {
                    if (this.value != select.val()) {
                        $(this).val(select.val()).change();
                    }
                });
            });

            input.data('localized', values)
                 .addClass('localized')
                 .val(values[field.i18n[0]] || '')
                 .on('keypress blur', function() {
                     values[select.val()] = this.value;
                     input.data('localized', values);
                 });

            return input;
        },
        renderLocaleSelectionBox: function(field) {
            var select = $('<select />')
                .attr('name', field.name + '.locale')
                .addClass('localizer');

            _.each(field.i18n, function(locale) {
                select.append($('<option />').attr('value', locale).text(locale));
            });

            return select;
        },
        createInputWithAttrs: function(tagName, field) {
            var input = $('<' + tagName + ' />')
                .attr('name', field.name)
                .data('type', field.formated_type)
                .addClass('input-xxlarge');
            return input;
        },
        wrapInBootstrapControlGroup: function(label, items) {
            var ctrlgrp = $('<div class="control-group" />');
            ctrlgrp.append('<label class="control-label">' + label + '</label>');
            var controls = $('<div class="controls" />').appendTo(ctrlgrp);
            _.each(items, function(item) { controls.append(item); });
            return ctrlgrp;
        },
        renderTabs: function() {
            var self = this;
            this.$('.nav-tabs a[data-related]').on('click', function() {
                var $this = $(this);
                var pan = self.$('div.tab-pane[id="' + $this.attr('href').substr(1) + '"]');

                if (!pan.hasClass('loaded')) {
                    self.renderRelatedTab($this.data('related'), pan);
                    pan.addClass('loaded');
                }
            });
        },
        renderRelatedTab: function(fieldName, pan) {
            var field = get_field(this.options.fields, fieldName);
            var data = {};

            data[field.value_controller.remote_id] = this.model[get_identifiers(this.options.fields)[0].name];

            var view = new Dashboard.RelatedModelsView({
                controller: field.value_controller.controller,
                remote_id: field.value_controller.remote_id,
                local_id: field.value_controller.local_id,
                name: field.name,
                query_data: data,
                model: field.related_model
            });

            pan.empty().append(view.render().el);
        }
    });

    /*
     * Represents an action to show a form
     */
    Dashboard.ObjectWidgetView = Dashboard.BaseWidgetView.extend({
        className: 'object-action-view',
        render: function() {
            var values = this.model || {};

            this.$el.empty();
            this.renderTitleWithIdentifier(this.options.model_name)
                .renderToolbar()
                .append(render_template('#object-action-tpl', { 
                    fields: filter_visible_fields(this.options.fields, 'view'), 
                    model: this.model 
                }));

            return this;
        }
    });

    /*
     * Represents an action to show a table of item
     */
    Dashboard.ListWidgetView = Dashboard.BaseWidgetView.extend({
        className: 'list-action-view',
        initialize: function() {
            Dashboard.ListWidgetView.__super__.initialize.apply(this);
            this.nbPages = 1;
            this.currentPage = 1;
        },
        render: function() {
            this.$body.empty();
            this.renderToolbar();
            this.$toolbar.disable();

            if (this.options.behaviors.paginated) {
                this.renderTable(this.model.data);
                this.renderPagination(this.model.count);
            } else {
                this.renderTable(this.model);
            }

            if (this.options.behaviors.filterable) {
                this.renderFilters();
            }

            if (this.options.behaviors.sortable) {
                this.makeSortable();
            }

            return this;
        },
        refresh: function(reloadPagination) {
            this.freeze();
            this.parent.action._call(_.extend({}, this.parent.action.data, this.overrideRequestData), 
                _.bind(function(resp) {
                    this.unfreeze();
                    if (this.options.behaviors.paginated) {
                        this.renderTable(resp.data);
                    } else {
                        this.renderTable(resp);
                    }
                    if (reloadPagination) {
                        this.renderPagination(resp.count);
                    }
                }, this),
                _.bind(function(message) {
                    this.unfreeze();
                    this.showError(message);
                }, this)
            );
        },
        loadPage: function(page) {
            this.currentPage = page;
            this.overrideRequestData.__offset = (page - 1) * this.options.behaviors.paginated.per_page;
            this.refresh();
        },
        renderTable: function(rows) {
            var self = this;
            var table = render_table(this.options.fields, rows, true);

            if (this.options.behaviors.sortable) {
                table.addClass('sortable');
                table.find('th').on('click', function() {
                    table.find('th.sorted').removeClass('sorted');
                    $(this).addClass('sorted').toggleClass('desc');
                    self.overrideRequestData.__offset = 0;
                    self.overrideRequestData.__sort = $(this).data('field');
                    self.overrideRequestData.__sort_order = $(this).hasClass('desc') ? 'desc' : 'asc';
                    self.refresh(true);
                });
            }

            var popoverTimeout;
            table.find('a.related')
                .on('click', function(e) {
                    Dashboard.app.runAction($(this).data('controller'), $(this).data('action'), $(this).data('params'));
                    e.preventDefault();
                })
                .on('mouseenter', function() {
                    var a = $(this);
                    popoverTimeout = setTimeout(function() {
                        Dashboard.api.call('get', Dashboard.config.base_url + '/' + a.data('controller') + '/view', a.data('params'), function(resp) {
                            var p = a.data('popover');
                            p.options.content = render_template('#related-popover-tpl', { data: resp });
                            p.setContent();
                        });
                        a.popover({
                            trigger: 'manual',
                            html: true,
                            content: '<em>Loading...</em>',
                            title: a.data('model'),
                            placement: 'bottom'
                        }).popover('show');
                    }, 500);
                })
                .on('mouseleave', function() {
                    clearTimeout(popoverTimeout);
                    $(this).popover('hide');
                });

            table.find('tbody tr').on('click', function() {
                self.$toolbar.enable();
            });

            if (this.$table) {
                this.$table.replaceWith(table);
            } else {
                table.appendTo(this.$el);
            }
            this.$table = table;
        },
        renderPagination: function(count) {
            if (this.$pagination) {
                this.$pagination.remove();
                this.$pagination = null;
            }

            this.nbPages = Math.ceil(count / this.options.behaviors.paginated.per_page);
            if (this.nbPages < 2) {
                return;
            }

            var pagination = $(render_template('#list-pagination-tpl', { nb_pages: this.nbPages }));

            this.$body.append(pagination);
            this.$pagination = pagination;

            var self = this;
            this.$('.pagination a').on('click', function(e) {
                if (!$(this).parent().hasClass('active') && !$(this).parent().hasClass('disabled')) {
                    var page = $(this).text();
                    if (page == 'Prev') {
                        self.loadPage(self.currentPage - 1);
                    } else if (page == 'Next') {
                        self.loadPage(self.currentPage + 1);
                    } else {
                        self.loadPage(parseInt(page, 10));
                    }

                    if (self.currentPage == 1) {
                        self.$('.pagination li:first-child').addClass('disabled');
                    } else {
                        self.$('.pagination li:first-child').removeClass('disabled');
                    }

                    self.$('.pagination .active').removeClass('active');
                    self.$('.pagination a[data-page="' + self.currentPage + '"]').parent().addClass('active');

                    if (self.currentPage == self.nbPages) {
                        self.$('.pagination li:last-child').addClass('disabled');
                    } else {
                        self.$('.pagination li:last-child').removeClass('disabled');
                    }
                }
                e.preventDefault();
            });
        },
        renderFilters: function() {
            this.addSidebar();
            var self = this;
            this.$sidebar.html(render_template('#list-filters-tpl', { fields: filter_visible_fields(this.options.fields, 'query') }));
            this.$sidebar.find('button.filter').on('click', function(e) {
                e.preventDefault();
                var filters = serialize(self.$sidebar, true);
                self.overrideRequestData.__filters = JSON.stringify(filters);
                self.refresh(true);
            });
            this.$sidebar.find('button.reset').on('click', function(e) {
                e.preventDefault();
                delete self.overrideRequestData.__filters;
                self.$sidebar.find(':input').each(function() {
                    if (this.type == 'checkbox') {
                        this.checked = false;
                    } else {
                        this.value = '';
                    }
                });
                self.refresh(true);
            });
        },
        makeSortable: function() {
            var table = this.$('table tbody');
            var url = this.options.behaviors.sortable.url;
            var originalIndex;

            table.sortable({
                axis: "y",
                start: function(e, ui) {
                    originalIndex = ui.item.index();
                    ui.item.find('input[type="radio"]')[0].checked = true;
                },
                stop: function(e, ui) {
                    var delta = ui.item.index() - originalIndex,
                        data = _.extend({ delta: delta }, serialize(ui.item));
                    Dashboard.api.call('post', url, data);
                }
            });
        },
        serialize: function() {
            return serialize_table(this.$table);
        },
        freeze: function() {
            Dashboard.app.showOverlay(this.$el);
        },
        unfreeze: function() {
            Dashboard.app.hideOverlay();
        }
    });

    Dashboard.ActionView = Backbone.View.extend({
        tagName: 'div',
        className: 'action',
        initialize: function() {
            this.action = this.options.action;
            this.listenTo(this.action, 'input', this.renderInput);
            this.listenTo(this.action, 'before_execute', this.updateUrl);
            this.listenTo(this.action, 'response', this.renderResponse);
            this.listenTo(this.action, 'error', this.renderError);
            this.listenTo(this.action, 'redirect', this._redirectHandler);
        },
        render: function() {
            this.action.execute();
            return this;
        },
        renderInput: function() {
            this.updateUrl();
            this.view = new Dashboard.FormWidgetView(_.extend({
                field_visibility: ['edit'],
                tabs_for_related_models: false
            }, this.action.schema.input));
            this.view.options.refreshable = false;
            this.view.parent = this;
            this.listenTo(this.view, 'submit', this.execute);
            this.$el.empty().append(this.view.render().el);
            this.trigger('render', this);
        },
        renderResponse: function(data) {
            var view;
            if (this.action.schema.output.type == 'list') {
                view = new Dashboard.ListWidgetView(this.action.schema.output);
            } else if (this.action.schema.output.type == 'object') {
                view = new Dashboard.ObjectWidgetView(this.action.schema.output);
            } else if (this.action.schema.output.type == 'form') {
                view = new Dashboard.FormWidgetView(this.action.schema.output);
            } else {
                this.trigger('done');
                return;
            }

            view.parent = this;
            view.model = data;

            this.listenTo(view, 'tbclick', function(controller, action, data) {
                this.freeze();
                var action = new Dashboard.Action(controller, action);
                action.getSchema(_.bind(function(schema) {
                    if (schema.input.type == 'form' || schema.output.type != 'none') {
                        this._redirectHandler(action.controller, action.name, data);
                    } else {
                        this.listenTo(action, 'response', function() {
                            view.refresh(); 
                        });
                        this.listenTo(action, 'error', this.renderError);
                        action.execute(data);
                    }
                }, this));
            });

            this.listenTo(view, 'done', function() { this.trigger('done'); });
            this.listenTo(view, 'submit', this.pipe);

            this.view = view;
            this.$el.empty().append(view.render().el);
            this.trigger('render', this);
        },
        renderError: function(message) {
            this.unfreeze();
            if (this.view) {
                this.view.showError(message);
            }
        },
        execute: function(data) {
            this.freeze();
            this.action.execute(data); 
        },
        updateUrl: function(data) {
            var url = this.action.url;
            if (data && this.action.schema.input.type != 'form') {
                var params = $.param(data);
                if (params) {
                    url += '?' + params;
                }
            }
            Dashboard.router.navigate(url);
        },
        pipe: function(data) {
            this.view && this.view.freeze();
            var pipe = this.action.pipe(data);
        },
        _redirectHandler: function(controller, action, data) { 
            this.trigger('redirect', controller, action, data); 
        },
        refresh: function() {
            if (this.view) {
                this.view.refresh();
            }
        },
        freeze: function() {
            if (this.view) {
                this.view.freeze();
            }
        },
        unfreeze: function() {
            if (this.view) {
                this.view.unfreeze();
            }
        }
    });

    // ----------------------------------------------------

    Dashboard.Action = function(controller, name, data) {
        this.controller = controller;
        this.name = name;
        this.data = data || {};
        this.allow_flow = arguments.length > 3 ? arguments[3] : true;
        this.lastRequest = {};
        this.url = "/" + this.controller + "/" + this.name;
        this.schema_url = Dashboard.config.schema_base_url + this.url + "/_schema";
    };

    _.extend(Dashboard.Action.prototype, Backbone.Events, {
        getSchema: function(callback) {
            if (this.schema) {
                callback(this.schema);
                return;
            }
            Dashboard.api.get(RestEndpoint.cached(this.schema_url), _.bind(function(schema) {
                this.schema = schema;
                callback(schema);
            }, this));
        },
        execute: function(data) {
            data = data || this.data;
            this.getSchema(_.bind(function(schema) {
                if (!$.isEmptyObject(data) || schema.input.type != 'form') {
                    this.doExecute(data);
                } else {
                    this.trigger('input');
                }
            }, this));
            return this;
        },
        reExecute: function(overrideData) {
            var data = _.extend({}, this.lastRequest, overrideData || {});
            this.doExecute(data);
        },
        doExecute: function(data) {
            this.trigger('before_execute', data);
            this.lastRequest = data;
            this._call(data,
                _.bind(this.handleResponse, this),
                _.bind(this.handleError, this),
                this.override_url,
                this.override_method
            );
        },
        handleResponse: function(data) {
            if (this.allow_flow && this.schema.output.flow.indexOf('redirect') === 0) {
                if (this.schema.output.flow == 'redirect') {
                    data = null;
                } else if (this.schema.output.flow == 'redirect_with_id') {
                    data = build_pk(this.schema.output.fields, data);
                }
                this.trigger('redirect', this.controller, this.schema.output.next_action, data);
                return;
            }
            this.trigger('response', data);
        },
        handleError: function(message) {
            this.trigger('error', message);
        },
        pipe: function(data) {
            if (this.allow_flow && this.schema.output.flow == 'pipe') {
                var action = new Dashboard.Action(this.controller, this.schema.output.next_action, data);
                this.listenTo(action, 'response', function(resp) { this.trigger('response', resp); });
                this.listenTo(action, 'error', function(msg) { this.trigger('error', msg); });
                action.override_method = 'post';
                action.execute();
                return action;
            }
        },
        _call: function(data, callback, err_callback, url, method) {
            data = data || {};

            if (!method) {
                method = this.schema.input.type == 'form' ? 'post' : 'get';
            }

            if (!url) {
                url = this.schema.input.delegate || this.schema.input.url;
            }

            Dashboard.api.call(method, url, data, callback, err_callback);
        }
    });

    // ----------------------------------------------------
    
    Dashboard.Menu = Backbone.View.extend({
        tagName: 'ul',
        options: {
            render_submenus: true,
            show_icons: true
        },
        initialize: function() {
            this.items = {};
        },
        render: function(as_buttons, submenu) {
            var self = this;
            if (submenu) {
                this.$el.addClass('dropdown-menu');
            } else {
                this.$el.removeClass('dropdown-menu');
            }
            this.$el.empty();
            _(this.items).each(function(item, name) {
                var li = $('<li/>').appendTo(self.$el),
                    a = $('<a href="#" />').text(name);

                if (item instanceof Dashboard.Menu) {
                    if (self.options.render_submenus) {
                        var ul = item.render(false, true).$el;

                        a.html(name + ' <b class="caret"></b>');
                        if (!submenu) {
                            a.addClass("dropdown-toggle").data('toggle', "dropdown");
                        }

                        if (as_buttons) {
                            a.addClass('btn');
                            var grp = $('<div class="btn-group" />').append(a).append(ul);
                            li.append(grp);
                        } else {
                            if (submenu) {
                                li.addClass('dropdown-submenu');
                            } else {
                                li.addClass('dropdown');
                            }
                            li.append(a).append(ul);
                        }
                    } else {
                        a.appendTo(li).on('click', function(e) {
                            self.trigger('click', item);
                            e.preventDefault();
                        });
                    }
                } else {
                    a.attr('href', Dashboard.config.base_url + "/" + item.controller + '/' + item.name);
                    if (self.options.show_icons && item.icon) {
                        a.html('<i class="icon-' + item.icon + '"></i> ' + name);
                    }
                    if (as_buttons) {
                        a.addClass('btn');
                    }
                    a.appendTo(li).on('click', function(e) {
                        Dashboard.app.runAction(item.controller, item.name);
                        self.trigger('click', item);
                        e.preventDefault();
                    });
                }

            });
            return this;
        }
    });

    Dashboard.App = Backbone.View.extend({
        el: "body",
        initialize: function() {
            this.loaded = false;
            this.runActionOnLoad = false;
            this.runDefaultActionOnLoad = false;
            this.currentActionView = null;

            this.menu = new Dashboard.Menu({ render_submenus: false, show_icons: false });
            this.menu.on('click', function(item) {
                if (item instanceof Dashboard.Menu) {
                    $('#submenu').empty().append(item.render(true).$el).show();
                    $('#submenu .dropdown-toggle').dropdown();
                } else {
                    $('#submenu').hide();
                }
            });
            this.menu.$el.addClass('nav').appendTo(this.$('.navbar-inner'));

            this.$overlay = $('<div id="overlay" />').appendTo('body');

            this.refreshSchema();
        },
        refreshSchema: function() {
            Dashboard.api.get(Dashboard.config.schema_base_url + Dashboard.config.schema_url, _.bind(function(schema) {
                this.schema = schema;
                this.refreshMenu();
                this.loaded = true;
                this.defaultAction = [schema[0].controller, schema[0].name];
                if (this.runDefaultActionOnLoad) {
                    this.runActionOnLoad = this.defaultAction;
                }
                if (this.runActionOnLoad) {
                    this.runAction(this.runActionOnLoad[0], this.runActionOnLoad[1], this.runActionOnLoad[2]);
                    this.runActionOnLoad = false;
                }
            }, this));
        },
        refreshMenu: function() {
            _(this.schema).each(_.bind(function(action) {
                var menu_segs = action.menu.split('/');
                var parent_menu = this._buildMenuTree(_.initial(menu_segs));
                parent_menu.items[_.last(menu_segs)] = action;
            }, this));
            this.menu.render();
            $('.navbar .dropdown-toggle').dropdown();
        },
        _buildMenuTree: function(items) {
            if (items.length == 0) {
                return this.menu;
            }

            var parent = this._buildMenuTree(_.initial(items));
            var item = _.last(items);

            if (parent.items[item]) {
                return parent.items[item];
            }

            var menu_item = new Dashboard.Menu();
            parent.items[item] = menu_item;
            return menu_item;
        },
        runDefaultAction: function() {
            if (!this.loaded) {
                this.runDefaultActionOnLoad = true;
            } else {
                this.runAction(this.defaultAction[0], this.defaultAction[1]);
            }
        },
        runAction: function(controller, action, data) {
            if (!this.loaded) {
                this.runActionOnLoad = [controller, action, data];
                return;
            }

            var view = new Dashboard.ActionView({ action: new Dashboard.Action(controller, action, data) });

            this.listenTo(view, 'render', this.switchActionView);
            this.listenTo(view, 'redirect', this.runAction);
            this.listenTo(view, 'done', function() {
                if (view.previous) {
                    this.switchActionView(view.previous);
                    this.currentActionView.refresh();
                } else {
                    this.$('#main').empty();
                }
            });

            if (this.currentActionView) {
                this.currentActionView.freeze();
            }

            view.render();
        },
        switchActionView: function(actionView) {
            if (this.currentActionView) {
                this.currentActionView.unfreeze();
                this.currentActionView.$el.remove();
            }

            var menu;
            _(this.schema).each(function(a) {
                if (a.controller == actionView.action.controller && a.name == actionView.action.name) {
                    menu = a.menu;
                }
            });

            if (menu) {
                this.highlightMenu(menu);
            }

            actionView.previous = this.currentActionView;
            this.currentActionView = actionView;
            this.$('#main').append(actionView.$el);
        },
        highlightMenu: function(menuName) {
            var menu_segs = menuName.split('/'), top = _.head(menu_segs);
            if (this.menu.items[top]) {
                this.menu.trigger('click', this.menu.items[top]);
            }
        },
        showOverlay: function(el) {
            var p = el.offset();
            this.$overlay.css({
                top: p.top,
                left: p.left,
                width: el.width(),
                height: el.height()
            }).show();
        },
        hideOverlay: function() {
            this.$overlay.hide();
        }
    });

    Dashboard.Router = Backbone.Router.extend({
        initialize: function(options) {
            this.route(":controller/:action", "action");
            this.route("", "home");
        },
        home: function() {
            Dashboard.app.runDefaultAction();
        },
        action: function(controller, action) {
            action = action.replace(/\?*$/, '');
            var qs = '';
            if (action.indexOf('?') > -1) {
                qs = action.substr(action.indexOf('?') + 1);
                action = action.substr(0, action.indexOf('?'));
            }
            Dashboard.app.runAction(controller, action, this._parseQueryString(qs));
        },
        _parseQueryString: function(qs) {
            var result = {};
            if(!qs){
                return result;
            }
            $.each(qs.split('&'), function(index, value){
                if(value){
                    var param = value.split('=');
                    result[param[0]] = param[1];
                }
            });
            return result;
        }
    });

    // ----------------------------------------------------

    Dashboard.start = function(config) {
        Dashboard.config = config;
        Dashboard.ensure_config("base_url");
        Dashboard.ensure_config("api_base_url");
        Dashboard.ensure_config("schema_base_url");
        Dashboard.ensure_config("schema_url");

        Dashboard.api = new RestEndpoint(Dashboard.config.base_url + Dashboard.config.api_base_url);
        Dashboard.app = new Dashboard.App();
        Dashboard.router = new Dashboard.Router();
        Backbone.history.start({ root: Dashboard.config.base_url });
    };

});
