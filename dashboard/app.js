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
    };

    RestEndpoint.prototype.call = function(method, action, params, callback) {
        if (typeof(action) == 'function') {
            callback = action;
            action = '';
        }
        if (typeof(params) == 'function') {
            callback = params;
            params = {};
        }
        $.ajax({
            url: this.base_url + action,
            type: method,
            dataType: 'json',
            crossDomain: true,
            data: params || {}
        }).done(function(data) {
            callback(data.result);
        });
    };

    RestEndpoint.prototype.createEndpoint = function(url) {
        return new RestEndpoint(this.base_url + url);
    };

    _(['get', 'post', 'put', 'delete']).each(function(method) {
        RestEndpoint.prototype[method] = function(action, params, callback) {
            return this.call(method.toUpperCase(), action, params, callback);
        };
    });

    // ----------------------------------------------------

    /*
     * Represents a toolbar
     */
    Dashboard.ToolbarView = Backbone.View.extend({
        tagName: 'div',
        className: 'btn-toolbar',
        template: _.template($('#toolbar-tpl').html()),
        events: {
            "click button": "btnClick"
        },
        render: function() {
            this.$el.html(this.template({buttons: this.options.buttons}));
            return this;
        },
        btnClick: function(e) {
            this.trigger("btn-click", $(e.currentTarget).data('url'));
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
                        table += '<a href="' + f.link + '" class="action-link" data-' + f.name + '="' + row[f.name] + '">' + row[f.name] + '</a>';
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
        render: function() {
            if (this.model.input.type == 'form') {
                var view = new Dashboard.FormActionView({ fields: this.model.input.fields });
                this.listenTo(view, 'submit', this.executeAction);
                this.$el.empty().append(view.render().el);
            } else {
                this.executeAction();
            }
            return this;
        },
        renderResponse: function(data) {
            if (this.model.output.type == 'list') {
                var view = new Dashboard.ListActionView(this.model.output);
            } else if (this.model.output.type == 'object') {
                var view = new Dashboard.ObjectActionView(this.model.output);
            } else if (this.model.output.type == 'form') {
                var view = new Dashboard.FormActionView(this.model.output);
                if (this.model.output.url) {
                    this.listenTo(view, 'submit', function(data) {
                        this.trigger('pipe', this.model.output.url, data);
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
            var method = this.model.input.type == 'form' ? 'post' : 'get',
                data = data || {};

            this.$el.html('<span class="loading">Loading...</span>');
            Dashboard.api.call(method, this.model.input.url, data, _.bind(function(resp) {
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
            this.$el.on('click', 'a.action-link', function(e) {
                self.runAction($(this).attr('href'), $(this).data());
                e.preventDefault();
            });
        },
        render: function() {
            this.$el.html('<span class="loading">Loading...</span>');
            this.body = $('<div class="body" />');
            Dashboard.api.get(this.options.url, _.bind(function(actions) {
                var tb = new Dashboard.ToolbarView({buttons: actions});
                this.listenTo(tb, "btn-click", this.runAction);
                this.$el.empty().append(tb.render().el).append(this.body);
                this.defaultAction = actions[0].url;
                _.each(actions, _.bind(function(action) {
                    if (action.default) {
                        this.defaultAction = action.url;
                        this.runAction(action.url);
                        return;
                    }
                }, this));
            }, this));
            return this;
        },
        runAction: function(url, params) {
            this.body.empty().html('<span class="loading">Loading...</span>');
            Dashboard.api.get(url, _.bind(function(action) {
                var view = new Dashboard.ActionView({ model: action });
                this.listenTo(view, 'done', this.runDefaultAction);
                this.listenTo(view, 'pipe', this.runAction);
                this.body.empty().append(view.el);
                if (params) {
                    view.executeAction(params);
                } else {
                    view.render();
                }
            }, this));
        },
        runDefaultAction: function() {
            this.runAction(this.defaultAction);
        }
    });

    // ----------------------------------------------------

    Dashboard.App = Backbone.View.extend({
        el: "body",
        events: {
            "click .navbar .nav a": "showController"
        },
        initialize: function() {
            this.$('#main').html('<span class="loading">Loading...</span>');
            this.refreshControllersList();
        },
        refreshControllersList: function() {
            var nav = this.$('.navbar .nav').empty();
            Dashboard.api.get(Dashboard.config.controllers_url, function(data) {
                _(data).each(function(controller) {
                    nav.append($('<li><a href="' + controller.url + '">' + controller.name + '</a></li>'));
                });
                nav.children().first().children().click();
            });
        },
        showController: function(e) {
            this.$('#main').empty().append(new Dashboard.ControllerView({url: $(e.currentTarget).attr('href')}).render().el);
            e.preventDefault();
        }
    });

    // ----------------------------------------------------

    $.getJSON('config.json', function(data) {
        Dashboard.config = data;
        Dashboard.ensure_config("base_url");
        Dashboard.ensure_config("controllers_url");
        Dashboard.api = new RestEndpoint(Dashboard.config.base_url);
        var app = new Dashboard.App();
    });

});
