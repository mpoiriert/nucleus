
(function($) {
    Nucleus.DebugBar.CacheWidget = PhpDebugBar.Widget.extend({
        className: 'nucleus-phpdebugbar-widgets',
        render: function() {
            this.$status = $('<div class="status" />').appendTo(this.$el);

            this.$list = new PhpDebugBar.Widgets.ListWidget(null, function(li, call) {
                li.addClass('list-item');
                //Found will not be defined in case of set
                if (call.found !== undefined && !call.found) {
                    li.addClass('error');
                }

                $('<span class="field ' + call.method + '" title="Method" />').text(call.method).appendTo(li);
                $('<span class="field" title="Namespace" />').text(call.namespace).appendTo(li);
                $('<span class="field" title="Name" />').text(call.name).appendTo(li);
            });
            
            this.$el.append(this.$list.$el);

            this.bindAttr('data', function(data) {
                this.$list.set('data',data.calls);
                this.$status.empty()
                    .append($('<span />').text(data.calls.length + " calls have been mades"));
            });

        }
    });
})(jQuery);