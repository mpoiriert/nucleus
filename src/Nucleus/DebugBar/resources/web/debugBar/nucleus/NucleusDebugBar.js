(function($) {
    if (typeof Nucleus === "undefined") {
        Nucleus = {};
    }
    Nucleus.DebugBar = {};
    
    Nucleus.DebugBar.LinkIndicator = PhpDebugBar.DebugBar.Indicator.extend({
        tagName: 'a',
        render: function() {
            Nucleus.DebugBar.LinkIndicator.__super__.render.apply(this);
            this.bindAttr('href', function(href) {
                this.$el.attr('href', href);
            });
            
            this.bindAttr('target', function(target) {
                this.$el.attr('target', target);
            });
        }
    });
})(jQuery);