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
        if( href.indexOf('?') == -1 )
            href += '?ajax=1';
        else
            href += '&ajax=1';
        Modalbox.show( href, {title: thetitle, width: 500} );
    }
}

/*
    Hook menu items so they go to a popup

    usage:

    new popupHook( [ 'mi_managesite', 'mi_global_settings' ] );  

*/
var popupHook = Class.create();

popupHook.prototype = {

    initialize: function(ids) {
        var me = this;
        ids.each( function( id ) {
            if( $(id) )
            {
                var e = $(id);
                var href = e.href;
                e.href = 'javascript://hooked for popup ' + id;
                title = e.innerHTML.stripTags();
                Event.observe( id, 'click', me.onClick.bindAsEventListener( me, href, title ) );
            }
        } );
    },

    onClick: function( e, href, thetitle ) {
        if( href.indexOf('?') == -1 )
            href += '?popup=1';
        else
            href += '&popup=1';
        var dim = "height=600,width=900";
        var win = window.open( href, 'cchostextrawin', "status=1,toolbar=0,location=0,menubar=0,directories=0," +
                                      "resizable=1,scrollbars=1," + dim );
        win.title = thetitle;

    }
}

var popupHookup = Class.create();

popupHookup.prototype = {

    className: '',
    formatName: '',
    title: '',

    initialize: function(className,formatName,title) {
        this.className = className;
        this.formatName = formatName;
        this.title = title;
    },

    hookLinks: function() {
        var me = this;
        $$('.' + this.className).each( function(link) {
            var upload_id = link.id.match(/[0-9]+$/);
            Event.observe( link, 'click', me.onClick.bindAsEventListener( me, upload_id ) );
        });
    },

    onClick: function( e, upload_id ) {
        var url = query_url + 'f=html&t='+this.formatName+'&ids=' + upload_id;
        Modalbox.show( url, {title: this.title, width: 500} );
    }
}
