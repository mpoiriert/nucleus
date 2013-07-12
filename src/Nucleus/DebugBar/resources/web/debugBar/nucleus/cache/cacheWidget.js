
(function($) {
    /**
     * Widget for the displaying sql queries
     *
     * @this {CacheWidget}
     * @constructor
     * @param {Object} data
     */
    var CacheWidget = function(data) {
        this.element = $('<div class="nucleus-phpdebugbar-widgets" />');
        this.status = $('<div class="status" />').appendTo(this.element);

        this.list = new PhpDebugBar.Widgets.ListWidget(null, function(li, call) {
            li.addClass('list-item');
            
            //Found will not be defined in case of set
            if(call.found !== undefined && !call.found) {
                li.addClass('error');
            }
            
            $('<span class="field ' + call.method + '" title="Method" />').text(call.method).appendTo(li);
            $('<span class="field" title="Namespace" />').text(call.namespace).appendTo(li);
            $('<span class="field" title="Name" />').text(call.name).appendTo(li);
        });
        this.element.append(this.list.element);

        if (data) {
            this.setData(data);
        }
    };

    CacheWidget.prototype.setData = function(data) {
        this.list.setData(data.calls);
        this.status.empty()
            .append($('<span />').text(data.calls.length + " calls have been mades"));
    };

    PhpDebugBar.Widgets.CacheWidget = CacheWidget;
})(jQuery);