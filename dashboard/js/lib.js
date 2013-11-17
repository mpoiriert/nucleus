var Dashboard = {};

(function($) {
    
    Dashboard.utils = {};


    /*
     * Class to easily call a rest endpoint
     */
    var RestEndpoint = Dashboard.utils.RestEndpoint = function(base_url) {
        this.base_url = base_url;
        this.cache = {};
    };

    _.extend(RestEndpoint.prototype, {

        call: function(method, action, params, callback, err_callback) {
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
        },

        clearCache: function() {
            this.cache = {};
        },

        createEndpoint: function(url) {
            return new RestEndpoint(this.base_url + url);
        }

    });

    _(['get', 'post', 'put', 'delete']).each(function(method) {
        RestEndpoint.prototype[method] = function(action, params, callback) {
            return this.call(method.toUpperCase(), action, params, callback);
        };
    });

    RestEndpoint.cached = function(url) {
        return {url: url, cached: true, cache_id: url};
    };


    // ----------------------------------------------------
    

    var render_template = Dashboard.utils.render_template = function(id, data) {
        var tpl = _.template($(id).html());
        return tpl(_.extend({
            render_template: render_template,
            format_value: format_value
        }, data));
    };


    var serialize = Dashboard.utils.serialize = function(container, callback, ignoreEmptyValues) {
        var inputs = $(container).find(':input:not(button)');
        return serialize_inputs(inputs, callback, ignoreEmptyValues);
    }


    var serialize_inputs = Dashboard.utils.serialize_inputs = function(inputs, callback, ignoreEmptyValues) {
        var lock = inputs.length;
        var o = {};
        var map = {
            "int": function(v) { return parseInt(v, 10); },
            "double": parseFloat,
            "float": parseFloat,
            "bool": function(v) { return (v === true || v === "false" || v === 0); }
        };
        map['integer'] = map['int'];
        map['boolean'] = map['bool'];

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

            var v = $this.val() === '0' ? 0 : ($this.val() || '');
            var t = $this.data('type') || 'string';
            var localized = $this.hasClass('localized');
            var cast = map[t] !== undefined ? map[t] : function(v) { return v; };

            if ($this.hasClass('empty-to-null') && v == '') {
                v = null;
            }

            if (this.type == 'checkbox' && (t == 'bool' || t == 'boolean')) {
                if (!this.checked && ignoreEmptyValues) {
                    return done();
                }
                v = this.checked;
            }

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


    var extract_fields_from_object = Dashboard.utils.extract_fields_from_object = function(obj) {
        var fields = [];
        for (var k in obj) {
            fields.push({
                visibility: ['list', 'view'],
                name: k,
                identifier: false,
                formated_type: 'string',
                type: 'string',
                is_array: false
            });
        }
        return fields;
    };


    var get_identifiers = Dashboard.utils.get_identifiers = function(fields) {
        var idfields = [];
        for (var i = 0; i < fields.length; i++) {
            if (fields[i].identifier) {
                idfields.push(fields[i]);
            }
        }
        return idfields;
    };


    var build_pk = Dashboard.utils.build_pk = function(fields, data) {
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


    var get_field = Dashboard.utils.get_field = function(fields, name) {
        for (var i = 0; i < fields.length; i++) {
            if (fields[i].name == name) {
                return fields[i];
            }
        }
        return null;
    };


    var filter_visible_fields = Dashboard.utils.filter_visible_fields = function(fields, visibility) {
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


    var find_indexes_matching_pk = Dashboard.utils.find_indexes_matching_pk = function(values, id) {
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


    var find_object_matching_pk = Dashboard.utils.find_object_matching_pk = function(values, id) {
        return values[find_indexes_matching_pk(values, id)[0]];
    };


    var escape_html = Dashboard.utils.escape_html = function(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    };


    // ----------------------------------------------------


    var format_value = Dashboard.utils.format_value = function(v, f) {
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
                v = render_object(f.related_model.fields, v['data'], false);
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
        } else if (v === 0) {
            v = 0;
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
            return a;
        }

        return v;
    };


    var render_object = Dashboard.utils.render_object = function(fields, obj) {
        var with_identifier = arguments.length > 2 ? arguments[2] : true;
        var vfields = filter_visible_fields(fields, 'view');
        var table = $('<table class="table table-bordered table-striped table-condensed" />');

        _.each(vfields, function(f) {
            if (f.related_model && f.is_array) {
                return;
            }
            var tr = $('<tr />');
            var td = $('<td />');
            td.append(format_value(obj[f.name], f));
            if (f.identifier && with_identifier) {
                td.append($('<input type="hidden" name="' + f.name + '" />')
                    .val(obj[f.name]).data('type', f.formated_type).data('field', f.name));
            }
            tr.append('<td width="20%"><strong>' + f.title + '</strong></td>').append(td);
            table.append(tr);
        });

        return table;
    };


    var render_object_list = Dashboard.utils.render_object_list = function(fields, objs, with_value_controller) {
        var vfields = filter_visible_fields(fields, 'list');
        var table = $('<table class="table table-bordered table-striped table-condensed" />');

        var thead = $('<thead />').appendTo(table);
        var theadtr = $('<tr />').appendTo(thead).append('<th width="30" />');
        _.each(vfields, function(f) {
            theadtr.append($('<th />').data('field', f).text(f.title));
        });

        var tbody = $('<tbody />').appendTo(table);
        _.each(objs, function(obj, index) {
            var tr = $('<tr />');
            var pk = build_pk(fields, obj);
            var idtd = $('<td align="center" class="no-edit" />').appendTo(tr);
            if (!$.isEmptyObject(pk)) {
                var input = $('<input type="radio" name="id" class="no-serialize serialized-in-data" />')
                    .val(JSON.stringify(pk)).data('serialized', pk);
                idtd.append(input);
                tr.on('click', function() { input.prop('checked', true); });
            }

            _.each(vfields, function(f) {
                var td = $('<td />').data('field', f)
                                    .data('type', f.formated_type)
                                    .data('value', obj[f.name]);
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


    var serialize_object_list = Dashboard.utils.serialize_object_list = function(table) {
        return table.find('tbody td:first-child input:checked').data('serialized');
    };


    var show_modal = Dashboard.utils.show_modal = function(title, content) {
        var modal = $(render_template('#modal-tpl', { title: title, close_btn: true })).appendTo('body');
        modal.find('.modal-body').append(content);
        modal.modal();
        return modal;
    };


    var create_view_modal = Dashboard.utils.create_view_modal = function(title, view) {
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


    var connect_action_to_view = Dashboard.utils.connect_action_to_view = function(action, view) {
        action.on('response', function() {
            view.unfreeze();
        });
        action.on('error', _.bind(view.showError, view));
        view.on('submit', function(data) {
            view.freeze();
            action.execute(data);
        });
    };

})(jQuery);