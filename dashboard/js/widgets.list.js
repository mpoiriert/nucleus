(function($) {
    var utils = Dashboard.utils;

    /*
     * Represents an action to show a table of item
     */
    var ListView = Dashboard.Widgets.ListView = Dashboard.Widgets.BaseView.extend({

        className: 'list-action-view',

        initialize: function() {
            ListView.__super__.initialize.apply(this);
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
            this.$wrapper = $('<div class="table-wrapper" />').appendTo(this.$body);

            if (this.model) {
                if (this.options.behaviors.paginated) {
                    this.renderList(this.model.data);
                    this.renderPagination(this.model.count);
                } else {
                    this.renderList(this.model);
                }
            } else {
                this.$wrapper.text('Perform a search using the filter in the sidebar to start browsing');
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
            var table = utils.render_object_list(this.options.fields, rows, true);
            this.$wrapper.empty().append(table);

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

            this.$table = table;
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

                var paginator = $(utils.render_template('#list-pagination-tpl', { 
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

            this.$sidebar.html(utils.render_template('#list-filters-tpl', { 
                fields: utils.filter_visible_fields(this.options.fields, 'query'),
                values: values
            }));

            this.$sidebar.find('button.filter').on('click', function(e) {
                e.preventDefault();
                utils.serialize(self.$sidebar, function(filters) {
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
            callback(utils.serialize_object_list(this.$table));
        },

        toolbarClick: function(controller, action) {
            this.trigger('tbclick', controller, action, utils.serialize_object_list(this.$table));
        }

    });



})(jQuery);