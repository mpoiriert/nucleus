if (typeof(Dashboard) === 'undefined') {
    var Dashboard = {};
}

(function($) {
    var utils = Dashboard.utils;

    Dashboard.Widgets = {};

    /*
     * Base view class for actions
     */
    var BaseView = Dashboard.Widgets.BaseView = Backbone.View.extend({

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
                this.$toolbar = new Toolbar({ base_url: "#",
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
            utils.serialize(this.$body, _.bind(function(data) {
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

    /*
     * Represents a toolbar
     */
    var Toolbar = Dashboard.Widgets.Toolbar = Backbone.View.extend({

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
            this.$el.html(utils.render_template('#toolbar-tpl', {
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


    var HtmlView = Dashboard.Widgets.HtmlView = BaseView.extend({

        render: function() {
            this.$el.append(this.model);
            this.unfreeze();
        }

    });

})(jQuery);