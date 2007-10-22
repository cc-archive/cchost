var modalHook = Class.create();

modalHook.prototype = {

    initialize: function(ids) {
        var me = this;
        ids.each( function( id ) {
            if( $(id) )
            {
                var e = $(id);
                var href = e.href;
                e.href = 'javascript://hooked ' + id;
                title = e.innerHTML.stripTags();
                Event.observe( id, 'click', me.onClick.bindAsEventListener( me, href, title ) );
            }
        } );
    },

    onClick: function( e, href, thetitle ) {
        var url = href + q + 'ajax=1';
        Modalbox.show( url, {title: thetitle, width: 500} );
    }
}

var downloadHook = Class.create();

downloadHook.prototype = {

    initialize: function() {
    },

    hookLinks: function() {
        var me = this;
        $$('.download_hook').each( function(link) {
            var upload_id = link.id.match(/_ep_(.*)/)[1];
            Event.observe( link, 'click', me.onClick.bindAsEventListener( me, upload_id ) );
    }

    onClick: function( e, upload_id ) {
        var url = home_url + 'download/' + upload_id + q + 'ajax=1';
        Modalbox.show( url, {title: 'Download', width: 500} );
    }
}
