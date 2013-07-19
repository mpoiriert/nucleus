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
        tagName: 'div',
        events: {
            "submit form": "submitForm"
        },
        initialize: function() {
            this.service = this.options.service;
            this.action = this.options.action;
        },
        submitForm: function(e) {
            var form = this.$('form');
            Dashboard.api.call(form.attr('method'), form.attr('action'), form.serialize(), _.bind(function(data) {
                this.handleFormSubmited(data);
            }, this));
            e.preventDefault();
            return false;
        }
    });

    /*
     * Represents an action to show a form
     */
    Dashboard.FormActionView = Dashboard.ActionView.extend({
        className: 'form-action-view',
        template: _.template($('#form-action-tpl').html()),
        render: function() {
            var values = {};
            if (this.model) {
                values = this.model;
            }
            this.$el.append(this.template({ action: this.action, values: values }));
            return this;
        },
        handleFormSubmited: function() {
            this.trigger('done');
        }
    });

    /*
     * Represents an action to show a table of item
     */
    Dashboard.ListActionView = Dashboard.ActionView.extend({
        className: 'list-action-view',
        template: _.template($('#list-action-tpl').html()),
        render: function() {
            this.$el.empty();
            if (this.action.actions) {
                var tb = new Dashboard.ToolbarView({ buttons: this.action.actions });
                this.listenTo(tb, 'btn-click', this.toolbarClick);
                this.$el.append(tb.render().el);
            }
            this.$el.append(this.template({ action: this.action }));
            this.$('table').tablesorter();
            this.refresh();
            return this;
        },
        toolbarClick: function(url) {
            Dashboard.api.get(url, _.bind(function(action) {
                if (action.type != 'call') {
                    throw new Exception('Only call action is supported in this toolbar');
                }
                var form = this.$('form');
                form.attr('action', action.url).attr('method', action.method).submit();
            }, this));
        },
        handleFormSubmited: function() {
            this.refresh();
        },
        refresh: function() {
            Dashboard.api.call(this.action.method, this.action.url, _.bind(function(data) {
                var table = '';
                _.each(data, _.bind(function(row) {
                    table += '<tr><td><input type="checkbox" name="' + this.action.columns[0] + '[]" value="' + row[0] + '"></td>';
                    _.each(row, function(col) {
                        table += '<td>' + col + '</td>';
                    });
                    table += '</tr>';
                }, this));
                this.$('table tbody').html(table);
                this.$('table').trigger('update');
            }, this));
        }
    });

    Dashboard.ActionHandlers = {
        "list": {
            "view": Dashboard.ListActionView
        },
        "form": {
            "view": Dashboard.FormActionView
        },
        "call": {
            "execute": function(action) {
                Dashboard.api.call(action.method, action.url, function(data) {
                    alert(data);
                });
            }
        }
    };

    // ----------------------------------------------------

    /*
     * Loads the service definition and creates the service toolbar
     * Loads the default action if there is one
     */
    Dashboard.ServiceView = Backbone.View.extend({
        tagName: 'div',
        className: 'service',
        render: function() {
            this.$el.html('loading...');
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
        runAction: function(url) {
            Dashboard.api.get(url, _.bind(function(action) {
                if (!Dashboard.ActionHandlers[action.type]) {
                    throw new Exception("Unsupported action: " + action.type);
                }
                var handler = Dashboard.ActionHandlers[action.type];
                if (handler.view) {
                    view = new handler.view({service: this, action: action});
                    this.listenTo(view, 'done', this.runDefaultAction);
                    this.body.empty().append(view.render().el);
                } else {
                    handler.execute(action);
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
            "click .navbar .nav a": "showService"
        },
        initialize: function() {
            this.refreshServiceList();
        },
        refreshServiceList: function() {
            var serviceList = this.$('.navbar .nav').empty();
            Dashboard.api.get(Dashboard.config.services_url, function(data) {
                _(data).each(function(service) {
                    serviceList.append($('<li><a href="' + service.url + '" data-url="' + service.url + '">' + service.name + '</a></li>'));
                });
            });
        },
        showService: function(e) {
            this.$('#main').empty().append(new Dashboard.ServiceView({url: $(e.currentTarget).data('url')}).render().el);
            e.preventDefault();
        }
    });

    // ----------------------------------------------------

    $.getJSON('config.json', function(data) {
        Dashboard.config = data;
        Dashboard.ensure_config("base_url");
        Dashboard.ensure_config("services_url");
        Dashboard.api = new RestEndpoint(Dashboard.config.base_url);
        var app = new Dashboard.App();
    });

});
