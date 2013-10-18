(function($) {
    var utils = Dashboard.utils;

    /**
     * Represents a view to manage related models
     */
    var RelatedModelsView = Dashboard.Widgets.RelatedModelsView = Dashboard.Widgets.BaseView.extend({

        tagName: 'div',

        className: 'related-models-view',

        initialize: function() {
            RelatedModelsView.__super__.initialize.apply(this);
            this.field = this.options.field;
            this.data = this.options.data;
            this.value_controller = this.field.value_controller;
            this.model = this.field.related_model;
        },

        render: function() {
            this.$el.html('<em>Loading...</em>');
            this.refresh();
            return this;
        },

        refresh: function() {
            this.freeze();
            if (this.value_controller) {
                var action = new Dashboard.Action(this.value_controller.controller, 'list' + this.field.name);
                this.listenTo(action, 'response', this.renderList);
                action.execute(this.data);
            } else {
                this.renderList(this.data);
            }
        },

        renderList: function(data) {
            var self = this;
            var table = utils.render_object_list(_.filter(this.model.fields, _.bind(function(f) {
                return !this.value_controller || f.name != this.value_controller.remote_id; }, this)), data);

            var tb_groups = [[], []];
            if (_.contains(this.model.actions, 'view') && this.model.controller) {
                tb_groups[0].push({
                    name: 'view',
                    controller: '',
                    title: 'View',
                    icon: 'eye-open',
                    disabled: true
                });
            }
            if (_.contains(this.model.actions, 'remove')) {
                tb_groups[0].push({
                    name: 'remove',
                    controller: '',
                    title: 'Remove',
                    icon: 'trash',
                    disabled: true
                });
            }
            if (_.contains(this.model.actions, 'edit')) {
                tb_groups[0].push({
                    name: 'edit',
                    controller: '',
                    title: 'Edit',
                    icon: 'edit',
                    disabled: true
                });
                if (!_.contains(this.model.actions, 'noinline')) {
                    tb_groups[1].push({
                        name: 'edit_inline',
                        controller: '',
                        title: 'Edit all inline',
                        icon: 'edit'
                    });
                    tb_groups[1].push({
                        name: 'save_inline',
                        controller: '',
                        title: 'Save',
                        icon: 'hdd',
                        disabled: true
                    });
                }
            }
            if (_.contains(this.model.actions, 'create')) {
                tb_groups[0].push({
                    name: 'create',
                    controller: '',
                    title: 'Add New',
                    icon: 'plus'
                });
            }
            if (this.value_controller && _.contains(this.model.actions, 'add')) {
                tb_groups[0].push({
                    name: 'add',
                    controller: '',
                    title: 'Add Existing',
                    icon: 'plus'
                });
            }

            var tb = new Dashboard.Widgets.Toolbar({ groups: tb_groups });

            this.$el.empty().append(table).append(tb.render().el);
            this.unfreeze();

            if (!this.value_controller) {
                $('<input />')
                    .attr({ type: 'hidden', name: this.field.name})
                    .addClass('serialized-in-data')
                    .data('type', this.field.formated_type)
                    .data('serialized', this.data)
                    .appendTo(this.$el);
            }

            table.find('tbody tr').on('click', function() {
                tb.buttons().removeClass('disabled');
            });

            if (!_.contains(this.model.actions, 'noinline')) {
                this.activateInlineEdit(table, tb);
            }

            this.listenTo(tb, 'btn-click', function(controller, action) {
                if (action == 'view') {
                    this.trigger('redirect', this.model.controller, 'view', utils.serialize_object_list(table));
                } else if (action == 'remove') {
                    if (confirm('Are you sure?')) {
                        this.executeRemove(utils.serialize_object_list(table), function() {
                            self.refresh();
                        });
                    }
                } else if (action == 'edit') {
                    this.renderEdit(utils.serialize_object_list(table));
                } else if (action == 'add') {
                    this.renderAdd();
                } else if (action == 'create') {
                    this.renderCreate();
                } else if (action == 'edit_inline') {
                    table.find('tbody tr').dblclick();
                    tb.options.groups[1][0].disabled = true;
                    tb.options.groups[1][1].disabled = false;
                    tb.render();
                } else if (action == 'save_inline') {
                    table.find('input[type="text"]').prop('disabled', true);
                    var tr = table.find('tbody tr.editing.modified');
                    var lock = tr.length;
                    var done = function() {
                        if (--lock <= 0) {
                            self.refresh();
                        }
                    };
                    if (lock === 0) {
                        return done();
                    }
                    tr.each(function() { $(this).data('save')(done); });
                }
            });
        },

        activateInlineEdit: function(table, tb) {
            var self = this;
            table.find('tbody tr').on('dblclick', function() {
                var $tr = $(this);
                if ($tr.hasClass('editing')) {
                    return false;
                }

                tb.options.groups[1][1].disabled = false;
                tb.render();

                var save = function(callback) {
                    if (!$tr.hasClass('modified')) {
                        callback && callback();
                        return;
                    }
                    var inputs = $tr.find('input[type="text"]');
                    utils.serialize_inputs(inputs, function(data) {
                        inputs.prop('disabled', true);
                        if (!self.value_controller) {
                            var id = JSON.parse($tr.children().first().find('input').val());
                            var index = utils.find_indexes_matching_pk(self.data, id)[0];
                            self.data[index] = data;
                            data = null;
                        }
                        self.executeSave(data, callback);
                    });
                };

                $tr.data('save', save);
                $tr.addClass('editing').children().filter(':not(.no-edit)').each(function() {
                    var $this = $(this);
                    var input = $('<input type="text" />')
                        .attr('name', $this.data('field'))
                        .data('type', $this.data('type'))
                        .val($this.text());

                    input.on('keyup', function(e) {
                        if (e.which == 13) {
                            save(function() { self.refresh(); });
                            e.preventDefault();
                        } else {
                            $tr.addClass('modified');
                        }
                    });

                    $this.empty().append(input);
                });
            });
        },

        executeRemove: function(id, callback) {
            this.freeze();
            if (this.value_controller) {
                var action = new Dashboard.Action(this.value_controller.controller, 'remove' + this.field.name);
                this.listenTo(action, 'response', function(data) {
                    callback && callback();
                });
                action.execute(_.extend({}, this.data, id));
            } else {
                var index_to_delete = utils.find_indexes_matching_pk(this.data, id);
                index_to_delete.reverse();

                for (var i = 0; i < index_to_delete.length; i++) {
                    delete this.data[index_to_delete[i]];
                }

                this.updateData();
                callback && callback();
            }
        },

        executeSave: function(data, callback, err_callback) {
            if (typeof(data) == 'function') {
                callback = data;
                data = null;
            }
            if (this.value_controller) {
                var action = new Dashboard.Action(this.model.controller, 'save', data, false, false);
                this.listenTo(action, 'response', function() {
                    callback && callback();
                });
                if (err_callback) {
                    this.listenTo(action, 'error', err_callback);
                }
                action.execute();
            } else {
                this.updateData();
                callback && callback();
            }
        },

        renderEdit: function(id) {
            var view, modal;
            var self = this;

            var create_form = function(data) {
                view = self.createModelForm(data);
                modal = utils.create_view_modal(view.computeTitle(), view);
                view.render();
                return view;
            };

            if (this.value_controller) {
                var viewAction = new Dashboard.Action(this.model.controller, 'view', null, false, false);
                this.listenTo(viewAction, 'error', this.showError);
                this.listenTo(viewAction, 'response', function(data) {
                    create_form(data);
                    this.listenTo(view, 'submit', function(data) {
                        this.executeSave(data, function() {
                            modal.modal('hide').remove();
                            view = null;
                            modal = null;
                            self.refresh();
                        }, _.bind(view.showError, view));
                    })
                    modal.modal('show');
                });
                viewAction.execute(_.extend({}, id, this.data));
            } else {
                var index = utils.find_indexes_matching_pk(this.data, id)[0];
                create_form(this.data[index]);
                this.listenTo(view, 'submit', function(data) {
                    this.data[index] = data;
                    this.executeSave(function() {
                        modal.modal('hide').remove();
                        view = null;
                        modal = null;
                        self.refresh();
                    });
                });
                modal.modal('show');
            }
        },

        renderCreate: function() {
            var view = this.createModelForm(this.data, {
                fields: this.model.fields
            });
            var modal = utils.create_view_modal(view.computeTitle(), view);
            view.render();

            if (this.value_controller) {
                var action = new Dashboard.Action(this.model.controller, 'add', null, false, false);
                this.listenTo(action, 'error', view.showError);
                this.listenTo(view, 'submit', function(data) {
                    view.freeze();
                    action.execute(_.extend({}, data, this.data));
                });
                this.listenTo(action, 'response', function(data) {
                    view.unfreeze();
                    modal.modal('hide').detach();
                    view = null;
                    modal = null;
                    this.refresh();
                });
            } else {
                this.listenTo(view, 'submit', function(data) {
                    modal.modal('hide').detach();
                    view = null;
                    modal = null;
                    this.data.push(data);
                    this.updateData();
                    this.refresh();
                });
            }
            
            modal.modal('show');
        },

        createModelForm: function(data, options) {
            var options = _.extend({
                tabs_for_related_models: false,
                action_title: 'Create',
                show_title: false,
                model_name: this.model.name,
                fields: this.model.fields,
                field_visibility: ['edit', 'view'],
                model: data
            }, options || {});

            if (this.value_controller) {
                options.hidden_fields = [this.value_controller.remote_id];
            } else {
                options.send_modified_fields_only = false;
            }

            var view = new Dashboard.Widgets.FormView(options);
            return view;
        },

        renderAdd: function() {
            var viewAction = new Dashboard.Action(this.model.controller, 'view', null, false, false);
            var addAction = new Dashboard.Action(this.value_controller.controller, 'add' + this.field.name);
            var view = new Dashboard.Widgets.FormView({
                tabs_for_related_models: false,
                action_title: 'Add',
                show_title: false,
                model_name: this.model.name,
                force_edit: true,
                fields: _.filter(this.model.fields, _.bind(function(f) {
                    return f.name == this.value_controller.local_id; }, this))
            });
            var modal = utils.create_view_modal(view.computeTitle(), view);
            view.render();

            utils.connect_action_to_view(viewAction, view);
            addAction.on('error', view.showError);

            this.listenTo(viewAction, 'response', function(data) {
                addAction.execute(_.extend({}, data, this.data));
            });

            this.listenTo(addAction, 'response', function(data) {
                modal.modal('hide').detach();
                view = null;
                modal = null;
                this.refresh();
            });
            
            modal.modal('show');
        },

        updateData: function() {
            var data = {};
            data[this.field.name] = this.data;
            this.trigger('submit', data);
        }

    });


    /*
     * Represents an action to show a form
     */
    var ObjectView = Dashboard.Widgets.ObjectView = Dashboard.Widgets.BaseView.extend({

        className: 'object-action-view',

        options: {
            tabs_for_related_models: true
        },

        render: function() {
            this.$el.empty();
            this.renderTitleWithIdentifier(this.options.model_name).renderToolbar();

            var tabs = this.buildTabList();

            if (tabs.length > 1) {
                this.$el.append(utils.render_template('#tabs-tpl', { tabs: tabs }));
                this.renderObject(this.$('.tab-pane[id="tab-default"]'));
                this.$('.nav-tabs a[href="#tab-default"]').parent().addClass('active');
                this.renderTabs();
            } else {
                this.renderObject(this.$el);
            }

            this.unfreeze();
            return this;
        },

        renderObject: function(parent) {
            parent.append(utils.render_object(this.options.fields, this.model));
        },

        buildTabList: function() {
            var tabs = [];
            if (this.options.tabs_for_related_models) {
                tabs.push(['default', this.options.model_name]);
                for (var i = 0; i < this.options.fields.length; i++) {
                    if (this.options.fields[i].related_model && this.options.fields[i].is_array) {
                        tabs.push([this.options.fields[i].name, this.options.fields[i].title]);
                    }
                }
            }
            return tabs;
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
            var self = this;
            var field = utils.get_field(this.options.fields, fieldName);
            var data = {};

            if (field.value_controller) {
                if (typeof(this.model[field.value_controller.remote_id]) != 'undefined') {
                    data[field.value_controller.remote_id] = this.model[field.value_controller.remote_id];
                } else {
                    data[field.value_controller.remote_id] = this.model[utils.get_identifiers(this.options.fields)[0].name];
                }
            } else {
                data = this.model[fieldName];
            }

            var view = new RelatedModelsView({
                controller: this.parent.action.controller,
                field: field,
                data: data
            });

            this.listenTo(view, 'submit', function(data) {
                _.each(utils.get_identifiers(this.options.fields), function(f) {
                    data[f.name] = self.model[f.name];
                });
                this.trigger('submit', data, false);
            });

            this.listenTo(view, 'redirect', function(controller, action, data, force_input) {
                this.trigger('redirect', controller, action, data, force_input);
            });

            pan.empty().append(view.el);
            view.render();
        }

    });

})(jQuery);