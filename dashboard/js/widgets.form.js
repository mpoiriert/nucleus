(function($) {
    var utils = Dashboard.utils;

    /*
     * Represents an action to show a form
     */
    var FormView = Dashboard.Widgets.FormView = Dashboard.Widgets.ModelView.extend({

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
                this.$el.append(utils.render_template('#tabs-tpl', { tabs: tabs }));
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
            var fields = utils.filter_visible_fields(this.options.fields, this.options.field_visibility);

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
                if (value && field.related_model && !field.is_array) {
                    if (field.related_model.embed) {
                        value = value.data;
                    } else {
                        value = value.id;
                    }
                }
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
            if (!this.options.force_edit && !_.contains(field.visibility, 'edit')) {
                return this.wrapInBootstrapControlGroup(field, [
                    this.renderHiddenField(field, value),
                    $('<span class="valign" />').text(value)
                ]);
            }
            return this.renderEditableField(field, value);
        },

        renderEditableField: function(field, value, inputOnly) {
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

            if (inputOnly) {
                return input;
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

            var action = new Dashboard.Action(field.value_controller.controller, 'listRepr');
            action.on('response', function(data) {
                select.empty();
                for (var k in data) {
                    var s = value == k ? 'selected="selected"' : '';
                    select.append('<option value="' + k + '" ' + s + '>' + data[k] + '</option>');
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

            var mark_as_modified = function() {
                div.find('>p :input').addClass('modified');
            };

            var create_item = function(value) {
                if (field.field_options.values) {
                    var input = self.renderSelectField(field);
                } else {
                    var input = self.renderInputField(field);
                }
                input.val(value || '').on('change', mark_as_modified);

                var remove = $('<a href="javascript:">Remove</a>').on('click', function(e) {
                    $(this).parent().remove();
                    mark_as_modified();
                    e.preventDefault();
                });
                var p = $('<p />').append(input).append(' ').append(remove);
                p.insertBefore(add);
            };

            add.on('click', function(e) {
                create_item();
                mark_as_modified();
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

            if (field.identifier && this.options.send_modified_fields_only) {
                input.addClass('modified');
            }

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
            utils.serialize_inputs(this.$(selector), _.bind(function(data) {
                this.trigger('submit', data);
            }, this));

            e.preventDefault();
        }
        
    });

})(jQuery);