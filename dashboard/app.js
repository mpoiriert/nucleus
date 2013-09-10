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
    
    var serialize = function(container) {
        var ignoreEmptyValues = arguments.length > 1 ? arguments[1] : false;
        var o = {};
        var map = {
            "int": function(v) { return parseInt(v, 10); },
            "double": parseFloat,
            "float": parseFloat
        };
        $(container).find(':input:not(button)').each(function() {
            if (this.type == 'radio' && !this.checked) {
                return;
            }

            var $this = $(this), 
                v = this.value || '', 
                t = $this.data('type') || 'string', 
                is_array = false;

            if (!v && ignoreEmptyValues) {
                return;
            }

            if (this.type == 'checkbox' && t == 'bool') {
                v = this.checked;
            }

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
        options: {
            current_action: null
        },
        initialize: function() {
            this.base_url = this.options.base_url || '';
        },
        render: function() {
            this.$el.html(this.template({ 
                base_url: this.base_url, 
                buttons: this.options.buttons,
                current_action: this.options.current_action
            }));
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
        },
        formatValue: function(v) {
            if (v === false) {
                return 'false';
            } else if (v === true) {
                return 'true';
            }
            return v || '';
        }
    });

    /*
     * Represents an action to show a form
     */
    Dashboard.FormWidgetView = Dashboard.BaseWidgetView.extend({
        className: 'form-action-view form-horizontal',
        template: _.template($('#form-action-tpl').html()),
        render: function() {
            var values = this.model || {}, 
                title = this.parent.action.schema.title + ' ' + this.options.model_name;

            this.$el.empty();
            this.renderTitleWithIdentifier(title)
                .renderToolbar()
                .append(this.template({ fields: this.options.fields, values: values }));

            return this;
        }
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
            this.renderTitleWithIdentifier(this.options.model_name)
                .renderToolbar()
                .append(this.template({ fields: this.options.fields, model: this.model }));

            return this;
        }
    });

    /*
     * Represents an action to show a table of item
     */
    Dashboard.ListWidgetView = Dashboard.BaseWidgetView.extend({
        className: 'list-action-view',
        template: _.template($('#list-action-tpl').html()),
        initialize: function() {
            Dashboard.ListWidgetView.__super__.initialize.apply(this);
            this.nbPages = 1;
            this.currentPage = 1;
            _.each(this.options.fields, _.bind(function(f) {
                if (f.identifier) {
                    this.identifier = f;
                }
            }, this));
        },
        render: function() {
            this.$body.empty();
            this.renderToolbar().append(this.template({ fields: this.options.fields }));

            if (this.options.behaviors.sortable) {
                this.$('table').addClass('sortable');
                var self = this;
                this.$('table th').on('click', function() {
                    self.$('table th.sorted').removeClass('sorted');
                    $(this).addClass('sorted').toggleClass('desc');
                    self.overrideRequestData.__offset = 0;
                    self.overrideRequestData.__sort = $(this).data('field');
                    self.overrideRequestData.__sort_order = $(this).hasClass('desc') ? 'desc' : 'asc';
                    self.refresh(true);
                });
            }

            if (this.options.behaviors.paginated) {
                this.renderTable(this.model.data);
                this.renderPagination(this.model.count);
            } else {
                this.renderTable(this.model);
            }

            if (this.options.behaviors.filterable) {
                this.renderFilters();
            }

            return this;
        },
        refresh: function(reloadPagination) {
            this.freeze();
            this.parent.action._call(_.extend({}, this.parent.action.data, this.overrideRequestData), 
                _.bind(function(resp) {
                    this.unfreeze();
                    this.renderTable(resp.data);
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
            var table = '', self = this;
            _.each(rows, function(row) {
                table += '<tr><td><input type="radio" name="' + self.identifier.name + '" value="' + row[self.identifier.name] + '"></td>';
                _.each(self.options.fields, function(f) {
                    table += '<td>';
                    if (f.link) {
                        table += '<a href="' + Dashboard.config.base_url + "/" + f.link.controller + "/" + f.link.action + '?' + f.name + '=' + row[f.name] + 
                            '" class="action-link" data-controller="' + f.link.controller + '" data-action="' + f.link.action + 
                            '" data-params=\'{"' + f.name + '": "' + row[f.name] + '"}\'>' + self.formatValue(row[f.name]) + '</a>';
                    } else {
                        table += self.formatValue(row[f.name]);
                    }
                    table += '</td>';
                });
                table += '</tr>';
            });
            this.$('table tbody').html(table);
            this.$('table .action-link').on('click', function(e) {
                Dashboard.app.runAction($(this).data('controller'), $(this).data('action'), $(this).data('params'));
                e.preventDefault();
            });
        },
        renderPagination: function(count) {
            if (this.$pagination) {
                this.$pagination.remove();
            }

            this.nbPages = Math.ceil(count / this.options.behaviors.paginated.per_page);
            var tpl = _.template($('#list-pagination-tpl').html()),
                pagination = $(tpl({ nb_pages: this.nbPages }));

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
            var tpl = _.template($('#list-filters-tpl').html()), self = this;
            this.$sidebar.html(tpl({ fields: this.options.fields }));
            this.$sidebar.find('button.filter').on('click', function() {
                var filters = serialize(self.$sidebar, true);
                self.overrideRequestData.__filters = JSON.stringify(filters);
                self.refresh(true);
            });
            this.$sidebar.find('button.reset').on('click', function() {
                delete self.overrideRequestData.__filters;
                self.$sidebar.find(':input').val('');
                self.refresh(true);
            });
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
            this.listenTo(this.action, 'response', this.renderResponse);
            this.listenTo(this.action, 'error', this.renderError);
            this.listenTo(this.action, 'redirect', this._redirectHandler);
        },
        render: function() {
            this.action.execute();
            return this;
        },
        renderInput: function() {
            this.view = new Dashboard.FormWidgetView(this.action.schema.input);
            this.view.options.refreshable = false;
            this.view.parent = this;
            this.listenTo(this.view, 'submit', function(data) { 
                this.freeze();
                this.action.execute(data); 
            });
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

            if (this.action.schema.output.flow == 'pipe') {
                this.listenTo(view, 'submit', this.pipe);
            }

            var url = this.action.url;
            if (data && this.action.schema.input.type != 'form') {
                var params = $.param(data);
                if (params) {
                    url += '?' + params;
                }
            }
            Dashboard.router.navigate(url);

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
        pipe: function(data) {
            this.view && this.view.freeze();
            var pipe = this.action.pipe();
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
        this.lastRequest = {};
        this.url = "/" + this.controller + "/" + this.name;
        this.schema_url = Dashboard.config.schema_base_url + this.url + "/_schema";
    };

    _.extend(Dashboard.Action.prototype, Backbone.Events, {
        getSchema: function(callback) {
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
            this.trigger('before_execute');
            this.lastRequest = data;
            this._call(data,
                _.bind(this.handleResponse, this),
                _.bind(this.handleError, this),
                this.override_url,
                this.override_method
            );
        },
        handleResponse: function(data) {
            if (this.schema.output.flow.indexOf('redirect') === 0) {
                if (this.schema.output.flow == 'redirect') {
                    data = null;
                } else if (this.schema.output.flow == 'redirect_with_id') {
                    for (var i = 0; i < this.schema.output.fields.length; i++) {
                        if (this.schema.output.fields[i].identifier) {
                            var n = this.schema.output.fields[i].name, v = data[n];
                            data = {};
                            data[n] = v;
                        }
                    }
                }
                this.trigger('redirect', this.controller, this.schema.output.next_action, data);
                return;
            }
            this.trigger('response', data);
        },
        handleError: function(message) {
            this.trigger('error', message);
        },
        pipe: function() {
            if (this.schema.output.flow == 'pipe') {
                var action = new Dashboard.Action(this.controller, this.schema.output.next_action);
                this.listenTo(action, 'response', function(resp) { this.trigger('response', resp); });
                this.listenTo(action, 'error', function(msg) { this.trigger('error', msg); });
                action.override_method = 'post';
                action.execute();
                return action;
            }
        },
        _call: function(data, callback, err_callback, url, method) {
            data = data || {};
            var payload = data;

            if (!method) {
                method = this.schema.input.type == 'form' ? 'post' : 'get';
            }

            if (!url) {
                url = this.schema.input.delegate || this.schema.input.url;
            }

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
                    this.runAction(this.runActionOnLoad[0], this.runActionOnLoad[1]);
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

            var parent = this._buildMenuTree(_.initial(items)),
                item = _.last(items);

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
                this.runActionOnLoad = [controller, action];
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

            Dashboard.router.navigate(actionView.action.url.substr(1));
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
