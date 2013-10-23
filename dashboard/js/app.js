(function($) {
    var utils = Dashboard.utils;
    var widgets = Dashboard.Widgets;


    Dashboard.config = {};
    Dashboard.ensure_config = function(key) {
        if (typeof(Dashboard.config[key]) == 'undefined') {
            alert('Missing config key: ' + key);
            throw new Exception('Missing config key: ' + key);
        }
    };


    /**
     * Action
     */
    var Action = Dashboard.Action = function(controller, name, data, force_input) {
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
            Dashboard.api.get(utils.RestEndpoint.cached(this.schema_url),
                _.bind(function(schema) {
                    this.original_schema = this.schema = schema;
                    callback(schema);
                }, this)
            );
        },

        execute: function(data) {
            data = data || this.data;
            this.getSchema(_.bind(function(schema) {
                if (schema.input.type == 'none') {
                    this.handleResponse(null);
                } else if (schema.input.type != 'form' || (!$.isEmptyObject(data) && (!this.force_input || this.input_called))) {
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
                var action = new Action(data.controller, data.action, data.data, data.force_input);
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
                    data = utils.build_pk(this.schema.output.fields, data);
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
                var action = new Action(this.controller, this.schema.output.next_action, data);
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


    /**
     * ActionView
     */
    var ActionView = Dashboard.ActionView = Backbone.View.extend({

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
            this.view = new widgets.FormView(_.extend({
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
                if (options.model_name) {
                    view = new widgets.ListView(options);
                } else {
                    view = new widgets.ArrayView(options);
                }
            } else if (options.type == 'object') {
                if (options.model_name) {
                    view = new widgets.ModelView(options);
                } else {
                    view = new widgets.ObjectView(options);
                }
            } else if (options.type == 'form') {
                view = new widgets.FormView(options);
            } else if (options.type == 'html') {
                view = new widgets.HtmlView(options);
            } else {
                this.trigger('done');
                return;
            }

            this.listenTo(view, 'tbclick', function(controller, action, data) {
                this.freeze();
                var action = new Action(controller, action);
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

    
    /**
     * Menu
     */
    var Menu = Dashboard.Menu = Backbone.View.extend({
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

                if (item instanceof Menu && _.size(item.items) == 1) {
                    var parent = item;
                    item = _.toArray(item.items)[0];
                    item.icon = parent.icon;
                }

                if (item instanceof Menu) {
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


    /**
     * App
     */
    var App = Dashboard.App = Backbone.View.extend({

        el: "body",

        initialize: function() {
            this.loaded = false;
            this.runActionOnLoad = false;
            this.runDefaultActionOnLoad = false;
            this.currentActionView = null;

            this.menu = new Menu({ render_submenus: false, show_icons: false });
            this.menu.on('click', function(item) {
                if (item instanceof Menu) {
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
                this.showAction(new Action(controller, action, data, force_input));
            }
        },

        showAction: function(action) {
            var view = new ActionView({action: action});

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


    /**
     * Router
     */
    var Router = Dashboard.Router = Backbone.Router.extend({

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


    /**
     * start
     */
    Dashboard.start = function(config) {
        Dashboard.config = config;
        Dashboard.ensure_config("base_url");
        Dashboard.ensure_config("api_base_url");
        Dashboard.ensure_config("schema_base_url");
        Dashboard.ensure_config("schema_url");

        Dashboard.api = new utils.RestEndpoint(Dashboard.config.base_url + Dashboard.config.api_base_url);
        Dashboard.app = new App();
        Dashboard.router = new Router();
        Backbone.history.start({ root: Dashboard.config.base_url });
    };

})(jQuery);