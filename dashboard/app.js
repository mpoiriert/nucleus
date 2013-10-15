var Dashboard = {};

$(function() {

    Dashboard.config = {};
    Dashboard.ensure_config = function(key) {
        if (typeof(Dashboard.config[key]) == 'undefined') {
            alert('Missing config key: ' + key);
            throw new Exception('Missing config key: ' + key);
        }
    };

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
        if (method === 'POST') {
            payload = { data: JSON.stringify(params) };
        }

        $.ajax({
            url: this.base_url + action,
            type: method,
            dataType: 'json',
            crossDomain: true,
            data: payload,
            beforeSend: function(xhr) {
                xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            }
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
            render_template: render_template,
            format_value: format_value
        }, data));
    };

    var serialize = function(container, callback, ignoreEmptyValues) {
        var inputs = $(container).find(':input:not(button)');
        return serialize_inputs(inputs, callback, ignoreEmptyValues);
    }

    var serialize_inputs = function(inputs, callback, ignoreEmptyValues) {
        var lock = inputs.length;
        var o = {};
        var map = {
            "int": function(v) { return parseInt(v, 10); },
            "double": parseFloat,
            "float": parseFloat
        };

        var done = function() {
            if (--lock <= 0) {
                callback(o);
            }
        };

        if (inputs.length === 0) {
            return done();
        }

        inputs.each(function() {
            var $this = $(this);
            var self = this;

            if ($this.hasClass('no-serialize') || (this.type == 'radio' && !this.checked)) {
                return done();
            }

            if (this.type == 'file') {
                var reader = new FileReader();
                reader.onloadend = function(evt) {
                    o[self.name] = reader.result;
                    done();
                };
                reader.readAsDataURL(this.files[0]);
                return;
            }

            var v = $this.val() || '';
            var t = $this.data('type') || 'string';
            var localized = $this.hasClass('localized');
            var cast = map[t] !== undefined ? map[t] : function(v) { return v; };

            var is_array = t.indexOf('[]') > -1;
            var is_hash = t.indexOf('{}') > -1;
            if (is_array || is_hash) {
                t = t.substr(0, t.length - 2);
            }

            if ($this.hasClass('serialized-in-data')) {
                v = $this.data('serialized');
            } else if (localized) {
                v = $this.data('localized');
            } else {
                v = cast(v);
            }

            if (!v && ignoreEmptyValues) {
                return done();
            }

            if (this.type == 'checkbox' && (t == 'bool' || t == 'boolean')) {
                if (!this.checked && ignoreEmptyValues) {
                    return done();
                }
                v = this.checked;
            }

            if (is_hash) {
                var key = $this.data('key').val();
                if (typeof(o[this.name]) == 'undefined') {
                    o[this.name] = {};
                }
                o[this.name][key] = v;
            } else if (is_array) {
                if (typeof(o[this.name]) == 'undefined') {
                    o[this.name] = [];
                }
                o[this.name].push(v);
            } else {
                o[this.name] = v;
            }
            done();
        });
    };

    var get_identifiers = function(fields) {
        var idfields = [];
        for (var i = 0; i < fields.length; i++) {
            if (fields[i].identifier) {
                idfields.push(fields[i]);
            }
        }
        return idfields;
    };

    var build_pk = function(fields, data) {
        var pk = {};
        for (var i = 0; i < fields.length; i++) {
            if (fields[i].identifier && data[fields[i].name]) {
                if (fields[i].related_model) {
                    pk[fields[i].name] = data[fields[i].name].id;
                } else {
                    pk[fields[i].name] = data[fields[i].name];
                }
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
                vfields.push(fields[i]);
            }
        }
        return vfields;
    };

    var find_indexes_matching_pk = function(values, id) {
        var indexes = [];
        mainloop:
        for (var i = 0; i < values.length; i++) {
            for (var k in id) {
                if (!values[i] || values[i][k] != id[k]) {
                    continue mainloop;
                }
            }
            indexes.push(i);
        }
        return indexes;
    };

    var find_object_matching_pk = function(values, id) {
        return values[find_indexes_matching_pk(values, id)[0]];
    };

    escape_html = function(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    };

    var format_value = function(v, f) {
        var hover_embed = arguments.length > 2 ? arguments[2] : false;
        var orig_v = v;
        var enable_vc = true;

        if (f && _.contains(['object', 'array', 'hash'], f.type) && !f.related_model) {
            var a = $('<a href="#">view data</a>');
            a.on('click', function(e) {
                show_modal(f.name, JSON.stringify(v, null, "  ").replace(/\n/g, "<br>"));
                e.preventDefault();
            });
            return a;
        }

        if (f && f.i18n) {
            v = v[f.i18n[0]];
        }
        if (f && f.related_model && !f.is_array && v !== null) {
            if (f.related_model.embed) {
                enable_vc = false;
                v = render_object(f.related_model.fields, v['data']);
                if (hover_embed) {
                    v = $('<a href="#" class="related-hover" />')
                        .append(orig_v['repr'] + ' (#' + orig_v['id'] + ')')
                        .append($('<div />').append(v));
                }
            } else {
                v = v['repr'] + ' (#' + v['id'] + ')';
            }
            orig_v = orig_v['id'];
        } else if (v === false) {
            v = 'false';
        } else if (v === true) {
            v = 'true';
        } else {
            v = escape_html(v || '');
        }

        if (f && f.value_controller && enable_vc) {
            var url = Dashboard.config.base_url + "#" + f.value_controller.controller + "/edit?" + f.value_controller.remote_id + '=' + orig_v;
            var a = $('<a class="related" />').attr('href', url).text(v).data({
                model: f.related_model.name,
                controller: f.value_controller.controller,
                action: 'edit',
                params: _.object([[f.value_controller.remote_id, orig_v]])
            });
            //activate_related_popup(a);
            return a;
        }

        return v;
    };

    var activate_related_popup = function(item) {
        var popoverTimeout;
        item.on('click', function(e) {
            if (e.which != 2 && !e.ctrlKey) {
                Dashboard.app.runAction($(this).data('controller'), $(this).data('action'), $(this).data('params'));
                e.preventDefault();
            }
        })
        .on('mouseenter', function() {
            var a = $(this);
            popoverTimeout = setTimeout(function() {
                var action = new Dashboard.Action(a.data('controller'), 'view', a.data('params'));
                action.on('response', function(resp) {
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
                action.execute();
            }, 500);
        })
        .on('mouseleave', function() {
            clearTimeout(popoverTimeout);
            $(this).popover('hide');
        });
    };

    var render_object = function(fields, obj) {
        var vfields = filter_visible_fields(fields, 'view');
        var table = $('<table class="table table-bordered table-striped table-condensed" />');

        _.each(vfields, function(f) {
            if (f.related_model && f.is_array) {
                return;
            }
            var tr = $('<tr />');
            var td = $('<td />');
            td.append(format_value(obj[f.name], f));
            if (f.identifier) {
                td.append($('<input type="hidden" name="' + f.name + '" />')
                    .val(obj[f.name]).data('type', f.formated_type).data('field', f.name));
            }
            tr.append('<td width="20%"><strong>' + f.title + '</strong></td>').append(td);
            table.append(tr);
        });

        return table;
    };

    var render_object_list = function(fields, objs, with_value_controller) {
        var vfields = filter_visible_fields(fields, 'list');
        var table = $('<table class="table table-bordered table-striped table-condensed" />');

        var thead = $('<thead />').appendTo(table);
        var theadtr = $('<tr />').appendTo(thead).append('<th width="30" />');
        _.each(vfields, function(f) {
            theadtr.append($('<th />').data('field', f.name).text(f.title));
        });

        var tbody = $('<tbody />').appendTo(table);
        _.each(objs, function(obj, index) {
            var tr = $('<tr />');
            var pk = build_pk(fields, obj);
            var input = $('<input type="radio" name="id" class="no-serialize serialized-in-data" />')
                .val(JSON.stringify(pk)).data('serialized', pk);

            tr.append($('<td align="center" class="no-edit" />').append(input));
            tr.on('click', function() { input.prop('checked', true); });

            _.each(vfields, function(f) {
                var td = $('<td />').data('field', f.name).data('type', f.formated_type);
                td.append(format_value(obj[f.name], f, true));
                if (!_.contains(f.visibility, 'edit')) {
                    td.addClass('no-edit');
                    if (f.identifier) {
                        td.append($('<input type="hidden" name="' + f.name + '" />')
                            .val(obj[f.name]).data('type', f.formated_type).data('field', f.name));
                    }
                }
                tr.append(td);
            });

            tbody.append(tr);
        });

        return table;
    };

    var serialize_object_list = function(table) {
        return table.find('tbody td:first-child input:checked').data('serialized');
    };

    var show_modal = function(title, content) {
        var modal = $(render_template('#modal-tpl', { title: title, close_btn: true })).appendTo('body');
        modal.find('.modal-body').append(content);
        modal.modal();
        return modal;
    };

    var create_view_modal = function(title, view) {
        var modal = $(render_template('#modal-tpl', { title: title, close_btn: false })).appendTo('body');
        modal.find('.modal-body').append(view.el);
        modal.modal({ show: false });
        modal.on('show', function() {
            $(this).find('.modal-body').css({
                width: 'auto',
                height: 'auto',
               'max-height': '100%'
            });
        });
        return modal;
    };

    var connect_action_to_view = function(action, view) {
        action.on('response', function() {
            view.unfreeze();
        });
        action.on('error', _.bind(view.showError, view));
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
        className: 'toolbar',
        events: {
            "click a.btn": "btnClick"
        },
        options: {
            current_action: null
        },
        initialize: function() {
            this.base_url = this.options.base_url || '';
            if (!this.options.groups) {
                this.options.groups = [this.options.buttons];
            }
        },
        render: function() {
            this.$el.html(render_template('#toolbar-tpl', {
                base_url: this.base_url,
                groups: this.options.groups,
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
            var confirmmsg = a.data('confirm');
            if (confirmmsg && !confirm(confirmmsg)) {
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
        tagName: 'div',
        options: {
            done_on_empty_data: true,
            refreshable: true
        },
        initialize: function() {
            this.$el.attr('enctype', 'multipart/form-data');
            this.$body = this.$el;
            this.overrideRequestData = {};
            if (this.options.parent) {
                this.parent = this.options.parent;
            }
        },
        refresh: function() {
            if (!this.options.refreshable) {
                return;
            }
            this.freeze();
            this.parent.action._call(
                _.extend({}, this.parent.action.data, this.overrideRequestData),
                _.bind(function(resp) {
                    if (!resp && this.options.done_on_empty_data) {
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
            this.$toolbar.$el.detach();
            this.$el.empty().append(this.$toolbar.$el).append(row);
            this.$body = body;
        },
        toolbarClick: function(controller, action) {
            serialize(this.$body, _.bind(function(data) {
                this.trigger('tbclick', controller, action, data);
            }, this));
        },
        freeze: function() {
            Dashboard.app.showOverlay(this.$el);
            this.$('button').prop('disabled', true);
        },
        unfreeze: function() {
            this.$('button').prop('disabled', false);
            Dashboard.app.hideOverlay();
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
            var table = render_object_list(_.filter(this.model.fields, _.bind(function(f) {
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

            var tb = new Dashboard.ToolbarView({ groups: tb_groups });

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
                    this.trigger('redirect', this.model.controller, 'view', serialize_object_list(table));
                } else if (action == 'remove') {
                    if (confirm('Are you sure?')) {
                        this.executeRemove(serialize_object_list(table), function() {
                            self.refresh();
                        });
                    }
                } else if (action == 'edit') {
                    this.renderEdit(serialize_object_list(table));
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
                    serialize_inputs(inputs, function(data) {
                        inputs.prop('disabled', true);
                        if (!self.value_controller) {
                            var id = JSON.parse($tr.children().first().find('input').val());
                            var index = find_indexes_matching_pk(self.data, id)[0];
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
                var index_to_delete = find_indexes_matching_pk(this.data, id);
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
                modal = create_view_modal(view.computeTitle(), view);
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
                var index = find_indexes_matching_pk(this.data, id)[0];
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
            var modal = create_view_modal(view.computeTitle(), view);
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

            var view = new Dashboard.FormWidgetView(options);
            return view;
        },
        renderAdd: function() {
            var viewAction = new Dashboard.Action(this.model.controller, 'view', null, false, false);
            var addAction = new Dashboard.Action(this.value_controller.controller, 'add' + this.field.name);
            var view = new Dashboard.FormWidgetView({
                tabs_for_related_models: false,
                action_title: 'Add',
                show_title: false,
                model_name: this.model.name,
                force_edit: true,
                fields: _.filter(this.model.fields, _.bind(function(f) {
                    return f.name == this.value_controller.local_id; }, this))
            });
            var modal = create_view_modal(view.computeTitle(), view);
            view.render();

            connect_action_to_view(viewAction, view);
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
    Dashboard.ObjectWidgetView = Dashboard.BaseWidgetView.extend({
        className: 'object-action-view',
        options: {
            tabs_for_related_models: true
        },
        render: function() {
            this.$el.empty();
            this.renderTitleWithIdentifier(this.options.model_name).renderToolbar();

            var tabs = this.buildTabList();

            if (tabs.length > 1) {
                this.$el.append(render_template('#tabs-tpl', { tabs: tabs }));
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
            parent.append(render_object(this.options.fields, this.model));
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
            var field = get_field(this.options.fields, fieldName);
            var data = {};

            if (field.value_controller) {
                if (typeof(this.model[field.value_controller.remote_id]) != 'undefined') {
                    data[field.value_controller.remote_id] = this.model[field.value_controller.remote_id];
                } else {
                    data[field.value_controller.remote_id] = this.model[get_identifiers(this.options.fields)[0].name];
                }
            } else {
                data = this.model[fieldName];
            }

            var view = new Dashboard.RelatedModelsView({
                controller: this.parent.action.controller,
                field: field,
                data: data
            });

            this.listenTo(view, 'submit', function(data) {
                _.each(get_identifiers(this.options.fields), function(f) {
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

    /*
     * Represents an action to show a form
     */
    Dashboard.FormWidgetView = Dashboard.ObjectWidgetView.extend({
        tagName: 'form',
        className: 'form-action-view form-horizontal',
        events: {
            "submit": "handleFormSubmited"
        },
        options: {
            title: null,
            show_title: true,
            title_with_id: true,
            field_visibility: ['edit', 'view'],
            hidden_fields: [],
            force_edit: false,
            tabs_for_related_models: true,
            send_modified_fields_only: true
        },
        computeTitle: function() {
            var title = '';
            if (this.options.action_title) {
                title = this.options.action_title + ' ';
            } else if (this.parent && this.parent.action) {
                title = this.parent.action.schema.title + ' ';
            }
            if (this.options.model_name) {
                title += this.options.model_name;
            }
            return title;
        },
        render: function() {
            this.$el.empty();
            if (this.options.show_title) {
                var title = this.options.title || this.computeTitle();
                if (this.options.title_with_id) {
                    this.renderTitleWithIdentifier(title);
                } else {
                    this.renderTitle(title);
                }
            }
            this.renderToolbar();

            var tabs = this.buildTabList();

            if (tabs.length > 1) {
                this.$el.append(render_template('#tabs-tpl', { tabs: tabs }));
                this.renderForm(this.$('.tab-pane[id="tab-default"]'));
                this.$('.nav-tabs a[href="#tab-default"]').parent().addClass('active');
                this.renderTabs();
            } else {
                this.renderForm(this.$el);
            }

            this.unfreeze();
            return this;
        },
        renderForm: function(parent) {
            var values = this.model || {};
            var fields = filter_visible_fields(this.options.fields, this.options.field_visibility);

            fields = _.filter(fields, function(f) { return !f.related_model || !f.is_array; });

            this.renderFields(parent, fields, values);
            this.renderFormButtons(parent);
        },
        renderFormButtons: function(parent) {
            parent.append('<div class="form-actions"><button type="submit" class="btn btn-primary">Submit</button></div>');
        },
        renderFields: function(form, fields, model) {
            var self = this;
            this.usesCkEditor = false;

            _.each(fields, function(field) {
                var value = typeof(model[field.name]) == 'undefined' ? field.defaultValue : model[field.name];
                if (!_.contains(self.options.hidden_fields, field.name)) {
                    form.append(self.renderField(field, value));
                } else {
                    form.append(self.renderHiddenField(field, value));
                }
            });

            if (this.usesCkEditor) {
                setTimeout(function() { $('.ckeditor').ckeditor(); }, 200);
            }
        },
        renderField: function(field, value) {
            if (value && field.related_model && !field.is_array) {
                if (field.related_model.embed) {
                    value = value['data'];
                } else {
                    value = value['id'];
                }
            }
            if (!this.options.force_edit && !_.contains(field.visibility, 'edit')) {
                return this.wrapInBootstrapControlGroup(field, [
                    this.renderHiddenField(field, value),
                    $('<span class="valign" />').text(value)
                ]);
            }
            return this.renderEditableField(field, value);
        },
        renderEditableField: function(field, value) {
            var input;
            if (field.related_model && !field.is_array) {
                if (field.related_model.embed) {
                    input = this.renderRelatedModelInputs(field, value);
                } else if (field.value_controller && field.value_controller.embed) {
                    input = this.renderValueControllerSelectBox(field, value);
                } else {
                    input = this.renderInputField(field, value);
                }
            } else if (field.is_array) {
                input = this.renderArrayField(field, value);
            } else if (field.is_hash) {
                input = this.renderHashField(field, value);
            } else if (field.field_type == 'checkbox' || field.field_type == 'radio') {
                input = this.renderBooleanField(field, value);
            } else if (field.field_type == 'select') {
                input = this.renderSelectField(field, value);
            } else {
                input = this.renderInputField(field, value);
            }

            var inputs = [input];
            if (field.i18n) {
                this.makeFieldLocalizable(inputs, input, field, value);
            }

            return this.wrapInBootstrapControlGroup(field, inputs);
        },
        renderHiddenField: function(field, value) {
            return this.createInputWithAttrs('input', field).addClass('modified').attr('type', 'hidden').val(value);
        },
        renderValueControllerSelectBox: function(field, value) {
            var select = this.createInputWithAttrs('select', field).addClass('related');
            var m = field.related_model;

            select.append('<option>Loading...</option>');
            select.prop('disabled', true);

            var action = new Dashboard.Action(field.value_controller.controller, 'listAll', {__offset: -1});
            action.on('response', function(resp) {
                select.empty();
                for (var i = 0; i < resp.data.length; i++) {
                    var s = value == resp.data[i][m.identifier[0]] ? 'selected="selected"' : '';
                    select.append('<option value="' + resp.data[i][m.identifier[0]] + '" ' + s + '>' + resp.data[i][m.repr] + '</option>');
                }
                select.prop('disabled', false);
            });
            action.execute();

            return select;
        },
        renderRelatedModelInputs: function(field, value) {
            // TODO
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
        renderSelectField: function(field, value) {
            var select = this.createInputWithAttrs('select', field);
            _.each(field.field_options.values, function(v, k) {
                select.append($('<option />').text(v).val(k));
            });
            select.val(value);
            return select;
        },
        renderInputField: function(field, value) {
            var tagName = _.contains(['textarea', 'richtext'], field.field_type) ? 'textarea' : 'input';
            var input = this.createInputWithAttrs(tagName, field).val(value || '');
            var knownTypes = ['text', 'password', 'file'];
            if (tagName != 'textarea') {
                if (!_.contains(knownTypes, field.field_type)) {
                    input.attr('type', 'text');
                    input[field.field_type](field.field_options || {});
                } else {
                    input.attr('type', field.field_type);
                }
            } else if (field.field_type == 'richtext') {
                input.addClass('ckeditor').addClass('modified');
                this.usesCkEditor = true;
            }
            return input;
        },
        renderArrayField: function(field, value) {
            var self = this;
            var div = $('<div class="array-field" />');
            var add = $('<a href="javascript:" class="valign">Add</a>').appendTo(div);

            var create_item = function(value) {
                var input = self.renderInputField(field).val(value || '');
                var remove = $('<a href="javascript:">Remove</a>').on('click', function(e) {
                    $(this).parent().remove();
                    e.preventDefault();
                });
                var p = $('<p />').append(input).append(' ').append(remove);
                p.insertBefore(add);
            };

            add.on('click', function(e) {
                create_item();
                e.preventDefault();
            });

            if ($.isArray(value)) {
                _.each(value, create_item);
            }

            return div;
        },
        renderHashField: function(field, value) {
            var self = this;
            var div = $('<div class="hash-field" />');
            var add = $('<a href="javascript:" class="valign">Add</a>').appendTo(div);

            var create_item = function(key, value) {
                var keyinput = $('<input type="text" class="no-serialize" />').val(key || '');
                var input = self.renderInputField(field).data('key', keyinput).val(value || '')
                                .removeClass('input-xxlarge').addClass('input-xlarge');
                var remove = $('<a href="javascript:">Remove</a>').on('click', function(e) {
                    $(this).parent().remove();
                    e.preventDefault();
                });
                var p = $('<p />').append(keyinput).append(' ').append(input).append(' ').append(remove);
                p.insertBefore(add);
            };

            add.on('click', function(e) {
                create_item();
                e.preventDefault();
            });

            if (!$.isEmptyObject(value)) {
                _.each(value, function(v, k) { create_item(k, v); });
            } else if (field.field_options && field.field_options.possible_keys) {
                _.each(field.field_options.possible_keys, create_item);
            }

            return div;
        },
        makeFieldLocalizable: function(inputs, input, field, values) {
            var select = this.renderLocaleSelectionBox(field);
            inputs.unshift(select);

            values = values || {};

            select.data('target', input).on('change', function() {
                input.trigger('localeChanged');
                $('select.localizer').each(function() {
                    if (this.value != select.val()) {
                        $(this).val(select.val()).data('target').trigger('localeChanged');
                    }
                });
            });

            input.data('localized', values)
                 .addClass('localized')
                 .data('currentLocale', select.val())
                 .val(values[select.val()] || '')
                 .on('localeChanged', function() {
                    values[input.data('currentLocale')] = input.val();
                    input.data('localized', values).data('currentLocale', select.val());
                    input.val(values[select.val()] || '');
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
        createInputWithAttrs: function(tagName, field, override_type) {
            var input = $('<' + tagName + ' />')
                .attr('name', field.name)
                .data('type', override_type || field.formated_type)
                .addClass('input-xxlarge');

            input.on('keyup change', function(e) {
                input.addClass('modified');
            });

            return input;
        },
        wrapInBootstrapControlGroup: function(field, items) {
            var ctrlgrp = $('<div class="control-group" />');
            ctrlgrp.append('<label class="control-label">' + field.title + '</label>');
            var controls = $('<div class="controls" />').appendTo(ctrlgrp);
            _.each(items, function(item) { controls.append(item); });
            if (field.description) {
                controls.append('<span class="help-block">' + field.description + '</span>');
            }
            return ctrlgrp;
        },
        handleFormSubmited: function(e) {
            this.$(':input.localized').trigger('localeChanged');

            var selector = ':input:not(button)';
            if (this.options.send_modified_fields_only) {
                selector += '.modified';
            }
            serialize_inputs(this.$(selector), _.bind(function(data) {
                this.trigger('submit', data);
            }, this));

            e.preventDefault();
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

            if (this.options.input_data.__offset) {
                this.currentPage = this.options.input_data.__offset / this.options.behaviors.paginated.per_page + 1;
            }
        },
        render: function() {
            this.$body.empty();
            this.renderToolbar();
            this.$toolbar.disable();
            this.$toolbar.$el.affix();

            if (this.options.behaviors.paginated) {
                this.renderList(this.model.data);
                this.renderPagination(this.model.count);
            } else {
                this.renderList(this.model);
            }

            if (this.options.behaviors.filterable) {
                this.renderFilters();
            }

            this.unfreeze();
            return this;
        },
        refresh: function() {
            this.freeze();
            this.parent.updateUrl(this.overrideRequestData);
            this.parent.action._call(_.extend({}, this.parent.action.data, this.overrideRequestData), 
                _.bind(function(resp) {
                    this.unfreeze();
                    this.$toolbar.disable();
                    if (this.options.behaviors.paginated) {
                        this.renderList(resp.data);
                    } else {
                        this.renderList(resp);
                    }
                    this.renderPagination(resp.count);
                    window.scrollTo(0, 0);
                }, this),
                _.bind(function(message) {
                    this.unfreeze();
                    this.showError(message);
                    window.scrollTo(0, 0);
                }, this)
            );
        },
        loadPage: function(page) {
            this.currentPage = page;
            this.overrideRequestData.__offset = (page - 1) * this.options.behaviors.paginated.per_page;
            this.refresh();
        },
        renderList: function(rows) {
            var self = this;
            var table = render_object_list(this.options.fields, rows, true);
            var wrapper = $('<div class="table-wrapper" />').append(table);

            if (this.options.behaviors.orderable) {
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

            table.find('tbody tr').on('click', function() {
                self.$toolbar.enable();
            });

            if (this.options.behaviors.sortable) {
                this.makeSortable(table);
            }

            if (this.$wrapper) {
                this.$wrapper.replaceWith(wrapper);
            } else {
                wrapper.appendTo(this.$el);
            }
            this.$table = table;
            this.$wrapper = wrapper;
        },
        renderPagination: function(count) {
            var self = this;
            var maxDisplayedPages = 10;

            this.nbPages = Math.ceil(count / this.options.behaviors.paginated.per_page);
            if (this.nbPages < 2) {
                return;
            }

            var create_paginator = function() {
                var start_page = self.currentPage - maxDisplayedPages / 2;
                var end_page = self.currentPage + maxDisplayedPages / 2;
                if (start_page < 2) {
                    end_page = Math.min(self.nbPages - 1, end_page + 2 - start_page);
                    start_page = 2;
                } else if (end_page > (self.nbPages - 1)) {
                    start_page = Math.max(2, start_page - end_page - self.nbPages - 1);
                    end_page = self.nbPages - 1;
                }

                var paginator = $(render_template('#list-pagination-tpl', { 
                    nb_pages: self.nbPages,
                    current_page: self.currentPage,
                    start_page: start_page,
                    end_page: end_page
                }));

                paginator.find('li:not(.active):not(.disabled) a').on('click', function(e) {
                    var page = $(this).text();
                    if (page == 'Prev') {
                        self.loadPage(self.currentPage - 1);
                    } else if (page == 'Next') {
                        self.loadPage(self.currentPage + 1);
                    } else {
                        self.loadPage(parseInt(page, 10));
                    }
                    e.preventDefault();
                });

                return paginator;
            };

            this.$('.pagination').remove();
            this.$body.prepend(create_paginator());
            this.$body.append(create_paginator());

        },
        renderFilters: function() {
            this.addSidebar();
            var self = this;
            var values = {};
            if (this.parent.input_data.__filters) {
                values = JSON.parse(this.parent.input_data.__filters);
            }

            this.$sidebar.html(render_template('#list-filters-tpl', { 
                fields: filter_visible_fields(this.options.fields, 'query'),
                values: values
            }));
            this.$sidebar.find('button.filter').on('click', function(e) {
                e.preventDefault();
                serialize(self.$sidebar, function(filters) {
                    self.overrideRequestData.__filters = JSON.stringify(filters);
                    self.refresh();
                }, true);
            });
            this.$sidebar.find('button.reset').on('click', function(e) {
                e.preventDefault();
                self.overrideRequestData.__filters = '{}';
                self.$sidebar.find(':input').each(function() {
                    if (this.type == 'checkbox') {
                        this.checked = false;
                    } else {
                        this.value = '';
                    }
                });
                self.refresh();
            });
        },
        makeSortable: function(table) {
            var url = this.options.behaviors.sortable.url;
            var originalIndex;

            table.find('tbody').sortable({
                axis: "y",
                start: function(e, ui) {
                    originalIndex = ui.item.index();
                    ui.item.find('input[type="radio"]')[0].checked = true;
                },
                stop: function(e, ui) {
                    var delta = ui.item.index() - originalIndex;
                    var data = _.extend({ delta: delta }, ui.item.find('td:first-child input').data('serialized'));
                    Dashboard.api.call('POST', url, data);
                }
            });
        },
        serialize: function(callback) {
            callback(serialize_object_list(this.$table));
        },
        toolbarClick: function(controller, action) {
            this.trigger('tbclick', controller, action, serialize_object_list(this.$table));
        }
    });

    Dashboard.HtmlWidgetView = Dashboard.BaseWidgetView.extend({
        render: function() {
            this.$el.append(this.model);
            this.unfreeze();
        }
    });

    Dashboard.ActionView = Backbone.View.extend({
        tagName: 'div',
        className: 'action',
        initialize: function() {
            this.url_data = {};
            this.action = this.options.action;
            this.listenTo(this.action, 'input', this.renderInput);
            this.listenTo(this.action, 'before_execute', this.updateUrl);
            this.listenTo(this.action, 'response', this.renderResponse);
            this.listenTo(this.action, 'error', this.renderError);
            this.listenTo(this.action, 'redirect', this._redirectHandler);
            this.listenTo(this.action, 'done', function() { this.trigger('done'); });
        },
        render: function() {
            this.action.execute();
            return this;
        },
        renderInput: function(data) {
            this.updateUrl();
            this.view = new Dashboard.FormWidgetView(_.extend({
                field_visibility: ['edit'],
                tabs_for_related_models: false,
                title_with_id: false,
                model: data,
                parent: this,
                refreshable: true,
                send_modified_fields_only: false
            }, this.action.schema.input));
            this.listenTo(this.view, 'submit', this.execute);
            this.$el.empty().append(this.view.el);
            this.view.render();
            this.trigger('render', this);
        },
        renderResponse: function(data, input_data) {
            this.input_data = input_data;

            var options = _.extend({
                input_data: input_data,
                parent: this,
                model: data
            }, this.action.schema.output);

            var view;
            if (options.type == 'list') {
                view = new Dashboard.ListWidgetView(options);
            } else if (options.type == 'object') {
                view = new Dashboard.ObjectWidgetView(options);
            } else if (options.type == 'form') {
                view = new Dashboard.FormWidgetView(options);
            } else if (options.type == 'html') {
                view = new Dashboard.HtmlWidgetView(options);
            } else {
                this.trigger('done');
                return;
            }

            this.listenTo(view, 'tbclick', function(controller, action, data) {
                this.freeze();
                var action = new Dashboard.Action(controller, action);
                action.getSchema(_.bind(function(schema) {
                    this.unfreeze();
                    if (schema.output.type == 'form') {
                        this._redirectHandler(action.controller, action.name, data, true);
                    } else if (schema.output.type != 'file' && schema.output.type != 'none' && schema.output.next_action != '$url') {
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
            this.listenTo(view, 'redirect', this._redirectHandler);

            this.view = view;
            this.$el.empty().append(view.el);
            view.render();
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
            this.url_data = data || {};
            var url = this.action.url;
            if (data && !$.isEmptyObject(data) && this.action.schema.input.type != 'form') {
                var params = $.param(data);
                if (params) {
                    url += '?' + params;
                }
            }
            Dashboard.router.navigate(url);
        },
        pipe: function(data) {
            var link_events = arguments.length > 1 ? arguments[1] : true;
            if (link_events && this.view) {
                this.view.freeze();
            }
            var pipe = this.action.pipe(data, link_events);
        },
        _redirectHandler: function(controller, action, data, force_input) { 
            this.trigger('redirect', controller, action, data, force_input); 
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

    Dashboard.Action = function(controller, name, data, force_input) {
        this.controller = controller;
        this.name = name;
        this.data = data || {};
        this.force_input = force_input;
        this.allow_flow = arguments.length > 4 ? arguments[4] : true;
        this.lastRequest = {};
        this.url = "/" + this.controller + "/" + this.name;
        this.schema_url = Dashboard.config.schema_base_url + this.url + "/_schema";
        this.input_called = false;
    };

    _.extend(Dashboard.Action.prototype, Backbone.Events, {
        getSchema: function(callback) {
            if (this.original_schema) {
                this.schema = this.original_schema;
            }
            if (this.schema) {
                callback(this.schema);
                return;
            }
            Dashboard.api.get(RestEndpoint.cached(this.schema_url), _.bind(function(schema) {
                this.original_schema = this.schema = schema;
                callback(schema);
            }, this));
        },
        execute: function(data) {
            data = data || this.data;
            this.getSchema(_.bind(function(schema) {
                if (schema.input.type != 'form' || (!$.isEmptyObject(data) && (!this.force_input || this.input_called))) {
                    this.doExecute(data);
                } else {
                    this.input_called = true;
                    this.trigger('input', data);
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

            if (this.schema.output.type == 'file') {
                this._callInIframe(
                    data, 
                    _.bind(this.handleResponse, this), 
                    this.override_url, 
                    this.override_method
                );
            } else {
                this._call(data,
                    _.bind(this.handleResponse, this),
                    _.bind(this.handleError, this),
                    this.override_url,
                    this.override_method
                );
            }
        },
        handleResponse: function(data) {
            if (this.schema.output.type == 'dynamic') {
                this.schema = _.extend({}, this.schema, {output: data['schema']});
                data = data['data'];
            }
            if (this.schema.output.type == 'builder') {
                var action = new Dashboard.Action(data.controller, data.action, data.data, data.force_input);
                action.original_schema = data.schema;
                this.trigger('redirect', action);
                return;
            }
            if (this.allow_flow && this.schema.output.flow.indexOf('redirect') === 0) {
                var next_controller = this.controller;
                var next_action = this.schema.output.next_action;
                var force_input = false;
                if (this.schema.output.type == 'redirect') {
                    if (next_action == '$url') {
                        window.open(data);
                        this.trigger('done');
                        return;
                    }
                    next_controller = data.controller || next_controller;
                    next_action = data.action;
                    if (typeof(data['force_input']) != 'undefined') {
                        force_input = data.force_input;
                    }
                    data = data['data'];
                } else if (this.schema.output.flow == 'redirect') {
                    data = null;
                } else if (this.schema.output.flow == 'redirect_with_id') {
                    data = build_pk(this.schema.output.fields, data);
                }
                this.trigger('redirect', next_controller, next_action, data, force_input);
                return;
            }
            this.trigger('response', data, this.lastRequest);
        },
        handleError: function(message) {
            this.trigger('error', message);
        },
        pipe: function(data) {
            var link_events = arguments.length > 1 ? arguments[1] : true;
            if (this.allow_flow && this.schema.output.flow == 'pipe') {
                var action = new Dashboard.Action(this.controller, this.schema.output.next_action, data);
                if (link_events) {
                    this.listenTo(action, 'response', function(resp) { this.trigger('response', resp); });
                    this.listenTo(action, 'error', function(msg) { this.trigger('error', msg); });
                }
                action.override_method = 'POST';
                action.execute();
                return action;
            }
        },
        _call: function(data, callback, err_callback, url, method) {
            data = data || {};
            if (!method) {
                method = this.schema.input.type == 'form' ? 'POST' : 'GET';
            }
            if (!url) {
                url = this.schema.input.delegate || this.schema.input.url;
            }

            Dashboard.api.call(method, url, data, callback, err_callback);
        },
        _callInIframe: function(data, callback, url, method) {
            data = data || {};
            if (!method) {
                method = this.schema.input.type == 'form' ? 'POST' : 'GET';
            }
            if (!url) {
                url = this.schema.input.delegate || this.schema.input.url;
            }

            var id = Date.now();
            var iframe = $('<iframe name="action-execute-iframe-' + id + '" style="display: none" />').appendTo('body');
            var form = $('<form style="display: none" />').appendTo('body').attr({
                method: method,
                action: url,
                target: 'action-execute-iframe-' + id
            });

            form.append($('<input type="text" name="data" />').val(JSON.stringify(data)));

            form.submit().remove();
            callback(null);
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

                if (item instanceof Dashboard.Menu && _.size(item.items) == 1) {
                    var parent = item;
                    item = _.toArray(item.items)[0];
                    item.icon = parent.icon;
                }

                if (item instanceof Dashboard.Menu) {
                    if (self.options.render_submenus) {
                        var ul = item.render(false, true).$el;
                        var toggle = a;

                        if (!submenu) {
                            toggle = $('<a href="#" class="btn"><b class="caret"></b></a>');
                            var child = _.toArray(item.items)[0];
                            a.attr('href', Dashboard.config.base_url + '#' + child.controller + '/' + child.name);
                            a.on('click', function(e) {
                                if (e.which != 2 && !e.ctrlKey) {
                                    Dashboard.app.runAction(child.controller, child.name);
                                    self.trigger('click', child);
                                    e.preventDefault();
                                }
                            });
                        }

                        toggle.addClass("dropdown-toggle").data('toggle', "dropdown");

                        if (as_buttons) {
                            a.addClass('btn');
                            var grp = $('<div class="btn-group" />').append(a);
                            if (!submenu) {
                                grp.append(toggle);
                            }
                            grp.append(ul);
                            li.append(grp);
                        } else {
                            li.append(a);
                            if (submenu) {
                                li.addClass('dropdown-submenu');
                                li.append(toggle);
                            } else {
                                li.addClass('dropdown');
                            }
                            li.append(ul);
                        }
                    } else {
                        a.appendTo(li).on('click', function(e) {
                            self.trigger('click', item);
                            e.preventDefault();
                        });
                    }
                } else {
                    a.attr('href', Dashboard.config.base_url + "#" + item.controller + '/' + item.name);
                    if (self.options.show_icons && item.icon) {
                        a.html('<i class="icon-' + item.icon + '"></i> ' + name);
                    }
                    if (as_buttons) {
                        a.addClass('btn');
                    }
                    a.appendTo(li).on('click', function(e) {
                        if (e.which != 2 && !e.ctrlKey) {
                            Dashboard.app.runAction(item.controller, item.name);
                            self.trigger('click', item);
                            e.preventDefault();
                        }
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
        runAction: function(controller, action, data, force_input) {
            if (!this.loaded) {
                this.runActionOnLoad = [controller, action, data];
                return;
            }
            if (typeof(controller) != 'string') {
                this.showAction(controller);
            } else {
                this.showAction(new Dashboard.Action(controller, action, data, force_input));
            }
        },
        showAction: function(action) {
            var view = new Dashboard.ActionView({action: action});

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
            if (this.currentActionView && actionView != this.currentActionView) {
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
            window.scrollTo(0, 0);
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
