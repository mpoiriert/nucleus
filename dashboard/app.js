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
                callback(data.data);
            } else if (err_callback) {
                err_callback(data.message);
            }
            return;
        }
        $.ajax({
            url: this.base_url + action,
            type: method,
            dataType: 'json',
            crossDomain: true,
            data: params || {}
        }).done(_.bind(function(data) {
            this.cache[options.cache_id] = data.result;
            if (data.result.success) {
                callback(data.result.data);
            } else if (err_callback) {
                err_callback(data.result.message);
            }
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
            var a = $(e.currentTarget);
            this.trigger("btn-click", a.data('controller'), a.data('action'));
            e.preventDefault();
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
        renderToolbar: function() {
            if (this.options.actions) {
                this.$toolbar = new Dashboard.ToolbarView({ base_url: "#", buttons: this.options.actions });
                this.listenTo(this.$toolbar, 'btn-click', this.toolbarClick);
                this.$el.append(this.$toolbar.render().el);
            }
            return this.$el;
        },
        toolbarClick: function(controller, action) {
            this.trigger('pipe', controller, action, this.serialize());
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
            if (typeof(this.$error) === 'undefined') {
                this.$error = $('<div class="alert alert-error" />');
                if (this.$toolbar) {
                    this.$error.insertAfter(this.$toolbar);
                } else {
                    this.$error.prependTo(this.$el);
                }
            }
            this.$error.text(message);
        },
        serialize: function() {
            var o = {};
            var map = {
                "int": function(v) { return parseInt(v, 10); },
                "double": parseFloat,
                "float": parseFloat
            };
            this.$(':input:not(button)').each(function() {
                var $this = $(this), 
                    v = this.value || '', 
                    t = $this.data('type') || 'string', 
                    is_array = false;

                if (t.indexOf('[]') > -1) {
                    t = t.substr(0, t.length - 2);
                    is_array = true;
                }

                var cast = map[t] !== undefined ? map[t] : function(v) { return v; };

                if (is_array) {
                    v = _.map(v.split(","), cast);
                } else {
                    v = cast(v);
                }

                o[this.name] = v;
            });
            return o;
        }
    });

    /*
     * Represents an action to show a form
     */
    Dashboard.FormWidgetView = Dashboard.BaseWidgetView.extend({
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
    Dashboard.ObjectWidgetView = Dashboard.BaseWidgetView.extend({
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
    Dashboard.ListWidgetView = Dashboard.BaseWidgetView.extend({
        className: 'list-action-view',
        template: _.template($('#list-action-tpl').html()),
        initialize: function() {
            this.nbPages = 1;
            this.currentPage = 1;
            this.lastRequest = {};
            _.each(this.options.fields, _.bind(function(f) {
                if (f.identifier) {
                    this.identifier = f;
                }
            }, this));
        },
        render: function() {
            this.$el.empty();
            this.renderToolbar().append(this.template({ fields: this.options.fields }));

            if (this.options.sortable) {
                this.$('table').addClass('sortable');
                var self = this;
                this.$('table th').on('click', function() {
                    self.$('table th.sorted').removeClass('sorted');
                    $(this).addClass('sorted').toggleClass('desc');
                    self.lastRequest.__offset = 0;
                    self.lastRequest.__sort = $(this).data('field');
                    self.lastRequest.__sort_order = $(this).hasClass('desc') ? 'desc' : 'asc';
                    self.reloadData();
                });
            }

            if (this.options.paginated) {
                this.renderTable(this.model.data);
                this.renderPagination(this.model.count);
            } else {
                this.renderTable(this.model);
            }

            return this;
        },
        reloadData: function() {
            this.action.rawExecuteAction(this.lastRequest, _.bind(function(resp) {
                this.renderTable(resp.data);
            }, this));
        },
        loadPage: function(page) {
            this.currentPage = page;
            this.lastRequest.__offset = (page - 1) * this.options.items_per_page;
            this.reloadData();
        },
        renderTable: function(rows) {
            var table = '';
            _.each(rows, _.bind(function(row) {
                table += '<tr><td><input type="radio" name="' + this.identifier.name + '" value="' + row[this.identifier.name] + '"></td>';
                _.each(this.options.fields, function(f) {
                    table += '<td>';
                    if (f.link) {
                        table += '<a href="' + Dashboard.config.base_url + "/" + f.link.controller + "/" + f.link.action + '?' + f.name + '=' + row[f.name] + 
                            '" class="action-link" data-controller="' + f.link.controller + '" data-action="' + f.link.action + 
                            '" data-params=\'{"' + f.name + '": "' + row[f.name] + '"}\'>' + row[f.name] + '</a>';
                    } else {
                        table += row[f.name];
                    }
                    table += '</td>';
                });
                table += '</tr>';
            }, this));
            this.$('table tbody').html(table);
            this.$('table .action-link').on('click', function(e) {
                Dashboard.app.runAction($(this).data('controller'), $(this).data('action'), $(this).data('params'));
                e.preventDefault();
            });
        },
        renderPagination: function(count) {
            this.nbPages = Math.ceil(count / this.options.items_per_page);
            var tpl = _.template($('#pagination-tpl').html());
            this.$el.append(tpl({ nb_pages: this.nbPages }));

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
        }
    });

    Dashboard.ActionView = Backbone.View.extend({
        tagName: 'div',
        className: 'action',
        options: {
            params: {}
        },
        initialize: function() {
            this.controller = this.options.controller;
            this.name = this.options.name;
            this.action_url = "/" + this.controller + "/" + this.name;
            this.schema_url = Dashboard.config.schema_base_url + this.action_url + "/_schema";
        },
        render: function() {
            this.$el.html('<span class="loading">Loading...</span>');
            Dashboard.api.get(RestEndpoint.cached(this.schema_url), _.bind(function(schema) {
                this.schema = schema;
                if (!$.isEmptyObject(this.options.params) || schema.input.type != 'form') {
                    this.executeAction(this.options.params || {});
                } else {
                    this.renderInput();
                }
            }, this));
            return this;
        },
        renderInput: function() {
            this.view = new Dashboard.FormWidgetView({ fields: this.schema.input.fields });
            this.listenTo(this.view, 'submit', this.executeAction);
            this.$el.empty().append(this.view.render().el);
        },
        renderResponse: function(data) {
            if (this.schema.output.type == 'list') {
                this.view = new Dashboard.ListWidgetView(this.schema.output);
            } else if (this.schema.output.type == 'object') {
                this.view = new Dashboard.ObjectWidgetView(this.schema.output);
            } else if (this.schema.output.type == 'form') {
                this.view = new Dashboard.FormWidgetView(this.schema.output);
            } else {
                this.trigger('done');
                return;
            }

            this.view.action = this;
            this.view.model = data;
            this.listenTo(this.view, 'pipe', function(controller, action, data) {
                this.trigger('pipe', controller, action, data);
            });

            if (this.schema.output.pipe) {
                this.listenTo(this.view, 'submit', function(data) {
                    this.trigger('pipe', this.controller, this.schema.output.pipe, data);
                });
            }

            this.$el.empty().append(this.view.render().el);
        },
        executeAction: function(data) {
            this.view && this.view.freeze();
            this.rawExecuteAction(data, 
                _.bind(function(resp) {
                    if (this.schema.input.type != 'form') {
                        Dashboard.router.navigate(this.action_url + '?' + $.param(data));
                    }
                    this.renderResponse(resp);
                }, this),
                _.bind(function(message) {
                    if (this.view) {
                        this.view.showError(message);
                        this.view.unfreeze();
                    }
                }, this)
            );
        },
        rawExecuteAction: function(data, callback, err_callback) {
            var method = this.schema.input.type == 'form' ? 'post' : 'get',
                data = data || {},
                url = this.schema.input.url,
                payload = data;

            if (method === 'post') {
                payload = { data: JSON.stringify(data) };
            }

            Dashboard.api.call(method, url, payload, callback, err_callback);
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
            this.runDefaultActionOnLoad = false;
            this.currentAction = null;

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

            this.refreshSchema();
        },
        refreshSchema: function() {
            Dashboard.api.get(Dashboard.config.schema_base_url + Dashboard.config.schema_url, _.bind(function(schema) {
                this.schema = schema;
                this.refreshMenu();
                this.loaded = true;
                this.defaultAction = [schema[0].controller, schema[0].name];
                if (this.runDefaultActionOnLoad) {
                    this.runDefaultActionOnLoad = false;
                    this.runDefaultAction();
                }
            }, this));
        },
        refreshMenu: function() {
            _(this.schema).each(_.bind(function(action) {
                var menu_segs = action.menu.split('/');
                parent_menu = this.buildMenuTree(_.initial(menu_segs));
                parent_menu.items[_.last(menu_segs)] = action;
            }, this));
            this.menu.render();
            $('.navbar .dropdown-toggle').dropdown();
        },
        buildMenuTree: function(items) {
            if (items.length == 0) {
                return this.menu;
            }

            var parent = this.buildMenuTree(_.initial(items)),
                item = _.last(items);

            if (parent.items[item]) {
                return parent.items[item];
            }

            var menu_item = new Dashboard.Menu();
            parent.items[item] = menu_item;
            return menu_item;
        },
        runDefaultAction: function() {
            if (this.loaded) {
                this.runAction(this.defaultAction[0], this.defaultAction[1]);
            } else {
                this.runDefaultActionOnLoad = true;
            }
        },
        runAction: function(controller, action, params) {
            var view = new Dashboard.ActionView({ controller: controller, name: action, params: params || {} });
            this.listenTo(view, 'pipe', this.runAction);
            this.listenTo(view, 'done', function() {
                if (view.previous) {
                    this.switchActionView(view.previous);
                } else {
                    this.$('#main').empty();
                }
            });
            this.switchActionView(view);
        },
        switchActionView: function(action) {
            if (this.currentAction) {
                this.currentAction.$el.remove();
            }
            action.previous = this.currentAction;
            this.currentAction = action;
            this.$('#main').append(action.render().el);
            Dashboard.router.navigate(action.controller + "/" + action.name + "?" + $.param(action.options.params));
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
            action = action.replace(/\?/g, '');
            Dashboard.app.runAction(controller, action, this._parseQueryString());
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
        Backbone.history.start({ root: Dashboard.config.base_url });
    };

});
