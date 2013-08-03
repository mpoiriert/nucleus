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

    RestEndpoint.prototype.call = function(method, action, params, callback) {
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
            return callback(this.cache[options.cache_id]);
        }
        $.ajax({
            url: this.base_url + action,
            type: method,
            dataType: 'json',
            crossDomain: true,
            data: params || {}
        }).done(_.bind(function(data) {
            this.cache[options.cache_id] = data.result;
            callback(data.result);
        }, this));
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

    /*
     * Represents a toolbar
     */
    Dashboard.ToolbarView = Backbone.View.extend({
        tagName: 'div',
        className: 'btn-toolbar',
        template: _.template($('#toolbar-tpl').html()),
        events: {
            "click a": "btnClick"
        },
        initialize: function() {
            this.base_url = this.options.base_url || '';
        },
        render: function() {
            this.$el.html(this.template({ base_url: this.base_url, buttons: this.options.buttons }));
            return this;
        },
        btnClick: function(e) {
            this.trigger("btn-click", $(e.currentTarget).data('action'));
            e.preventDefault();
        }
    });

    /*
     * Base view class for actions
     */
    Dashboard.ActionView = Backbone.View.extend({
        tagName: 'form',
        events: {
            "submit": "handleFormSubmited"
        },
        renderToolbar: function() {
            if (this.options.actions) {
                var tb = new Dashboard.ToolbarView({ buttons: this.options.actions });
                this.listenTo(tb, 'btn-click', this.toolbarClick);
                this.$el.append(tb.render().el);
            }
            return this.$el;
        },
        toolbarClick: function(url) {
            this.$el.attr('action', url).submit();
        },
        handleFormSubmited: function(e) {
            this.trigger('submit', this.$el.serialize());
            e.preventDefault();
        }
    });

    /*
     * Represents an action to show a form
     */
    Dashboard.FormActionView = Dashboard.ActionView.extend({
        className: 'form-action-view',
        template: _.template($('#form-action-tpl').html()),
        render: function() {
            var values = this.model || {};
            this.$el.attr('method', 'post').html(
                this.template({ fields: this.options.fields, values: values }));
            return this;
        },
    });

    /*
     * Represents an action to show a form
     */
    Dashboard.ObjectActionView = Dashboard.ActionView.extend({
        className: 'object-action-view',
        template: _.template($('#object-action-tpl').html()),
        render: function() {
            var values = this.model || {};
            this.$el.empty();
            this.renderToolbar().append(this.template({ fields: this.options.fields, model: this.model }));
            return this;
        },
    });

    /*
     * Represents an action to show a table of item
     */
    Dashboard.ListActionView = Dashboard.ActionView.extend({
        className: 'list-action-view',
        template: _.template($('#list-action-tpl').html()),
        render: function() {
            this.$el.empty();
            this.renderToolbar().append(this.template({ fields: this.options.fields }));
            this.$('table').tablesorter();
            this.refresh();
            return this;
        },
        refresh: function() {
            var table = '', id;
            _.each(this.options.fields, function(f) {
                if (f.identifier) {
                    id = f;
                }
            });
            _.each(this.model, _.bind(function(row) {
                table += '<tr><td><input type="radio" name="' + id.name + '" value="' + row[id.name] + '"></td>';
                _.each(this.options.fields, function(f) {
                    table += '<td>';
                    if (f.link) {
                        table += '<a href="' + Dashboard.config.base_url + "/" + f.link.controller + "/" + f.link.action + '?' + f.name + '=' + row[f.name] + 
                            '" class="action-link" data-action="' + f.link.action + '" data-params=\'{"' + f.name + '": "' + row[f.name] + '"}\'>' + row[f.name] + '</a>';
                    } else {
                        table += row[f.name];
                    }
                    table += '</td>';
                });
                table += '</tr>';
            }, this));
            this.$('table tbody').html(table);
            this.$('table').trigger('update');
        }
    });

    Dashboard.ActionView = Backbone.View.extend({
        tagName: 'div',
        className: 'action',
        initialize: function() {
            this.action_url = "/" + this.options.controller + "/" + this.options.name;
            this.schema_url = Dashboard.config.schema_base_url + this.action_url + "/_schema";
        },
        render: function() {
            this.$el.html('<span class="loading">Loading...</span>');
            Dashboard.api.get(RestEndpoint.cached(this.schema_url), _.bind(function(schema) {
                this.schema = schema;
                if ((this.options.params && !$.isEmptyObject(this.options.params)) || schema.input.type != 'form') {
                    this.executeAction(this.options.params || {});
                } else {
                    this.renderInput();
                }
            }, this));
            return this;
        },
        renderInput: function() {
            var view = new Dashboard.FormActionView({ fields: this.schema.input.fields });
            this.listenTo(view, 'submit', this.executeAction);
            this.$el.empty().append(view.render().el);
        },
        renderResponse: function(data) {
            if (this.schema.output.type == 'list') {
                var view = new Dashboard.ListActionView(this.schema.output);
            } else if (this.schema.output.type == 'object') {
                var view = new Dashboard.ObjectActionView(this.schema.output);
            } else if (this.schema.output.type == 'form') {
                var view = new Dashboard.FormActionView(this.schema.output);
                if (this.schema.output.url) {
                    this.listenTo(view, 'submit', function(data) {
                        this.trigger('pipe', this.schema.output.url, data);
                    });
                }
            } else {
                this.trigger('done');
                return;
            }
            view.model = data;
            this.$el.empty().append(view.render().el);
        },
        executeAction: function(data) {
            var method = this.schema.input.type == 'form' ? 'post' : 'get',
                data = data || {};

            this.$el.html('<span class="loading">Loading...</span>');
            Dashboard.api.call(method, this.schema.input.url, data, _.bind(function(resp) {
                if (method == 'get') {
                    Dashboard.router.navigate(this.action_url + '?' + $.param(data));
                }
                this.renderResponse(resp);
            }, this));
        }
    });

    // ----------------------------------------------------

    /*
     * Loads the controller definition and creates the controller toolbar
     * Loads the default action if there is one
     */
    Dashboard.ControllerView = Backbone.View.extend({
        tagName: 'div',
        className: 'controller',
        initialize: function() {
            var self = this;
            this.schema_url = Dashboard.config.schema_base_url + "/" + this.options.name + "/_schema";
            this.$el.on('click', 'a.action-link', function(e) {
                self.runAction($(this).data('action'), $(this).data('params'));
                e.preventDefault();
            });
        },
        render: function() {
            this.$el.html('<span class="loading">Loading...</span>');
            this.body = $('<div class="body" />');
            Dashboard.api.get(RestEndpoint.cached(this.schema_url), _.bind(function(schema) {
                this.schema = schema;

                var tb_base_url = Dashboard.config.base_url + "/" + this.options.name + "/",
                    tb = new Dashboard.ToolbarView({ base_url: tb_base_url, buttons: schema });
                this.listenTo(tb, "btn-click", this.runAction);
                this.$el.empty().append(tb.render().el).append(this.body);

                this.defaultAction = schema[0].name;
                _.each(schema, _.bind(function(action) {
                    if (action.default) {
                        this.defaultAction = action.name;
                        return;
                    }
                }, this));

                if (this.options.action) {
                    this.runAction(this.options.action, this.options.params);
                } else {
                    this.runAction(this.defaultAction);
                }
            }, this));
            return this;
        },
        runAction: function(name, params) {
            var view = new Dashboard.ActionView({ controller: this.options.name, name: name, params: params });
            this.listenTo(view, 'done', this.runDefaultAction);
            this.listenTo(view, 'pipe', this.runAction);
            this.body.empty().append(view.render().el);
            Dashboard.router.navigate(this.options.name + "/" + name + "?" + $.param(params || {}));
        },
        runDefaultAction: function() {
            this.runAction(this.defaultAction);
        }
    });

    // ----------------------------------------------------

    Dashboard.App = Backbone.View.extend({
        el: "body",
        events: {
            "click .navbar .nav a": "onNavClick"
        },
        initialize: function() {
            this.$('#main').html('<span class="loading">Loading...</span>');
            this.loaded = false;
            this.runDefaultControllerOnLoad = false;
            this.refreshControllersList();
        },
        refreshControllersList: function() {
            var nav = this.$('.navbar .nav').empty();
            Dashboard.api.get(Dashboard.config.schema_base_url + Dashboard.config.schema_url, _.bind(function(schema) {
                this.schema = schema;
                _(schema).each(function(controller) {
                    nav.append($('<li><a href="' + Dashboard.config.base_url + "/" + controller.name + 
                        '" data-controller="' + controller.name + '">' + controller.title + '</a></li>'));
                });
                this.loaded = true;
                if (this.runDefaultControllerOnLoad) {
                    this.runDefaultControllerOnLoad = false;
                    this.runDefaultController();
                }
            }, this));
        },
        runDefaultController: function() {
            if (this.loaded) {
                this.$('.navbar .nav').children().first().children().click();
            } else {
                this.runDefaultControllerOnLoad = true;
            }
        },
        runController: function(name, action, params) {
            this.$('#main').empty().append(new Dashboard.ControllerView({name: name, action: action, params: params}).render().el);
        },
        onNavClick: function(e) {
            var name = $(e.currentTarget).data('controller');
            Dashboard.router.navigate(name);
            this.runController(name);
            e.preventDefault();
        }
    });

    Dashboard.Router = Backbone.Router.extend({
        initialize: function(options) {
            this.route(":controller/:action", "action");
            this.route(":controller", "controller");
            this.route("", "home");
        },
        home: function() {
            Dashboard.app.runDefaultController();
        },
        controller: function(controller) {
            Dashboard.app.runController(controller);
        },
        action: function(controller, action) {
            Dashboard.app.runController(controller, action, this._parseQueryString());
        },
        _parseQueryString: function() {
            var qs = window.location.search.substring(1), result = {};
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
        Backbone.history.start({ pushState: true, root: Dashboard.config.base_url });
    };

});
