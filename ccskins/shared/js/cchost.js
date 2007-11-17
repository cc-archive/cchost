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

var ratingsHooks = Class.create();

ratingsHooks.prototype = {

    full_star_url: null,
    null_star_url: null,
    return_macro: null,
    initialize: function(null_star,full_star,return_macro) {
        this.full_star_url = full_star;
        this.null_star_url = null_star;
        this.return_macro = return_macro;
        var me = this;
        $$('.rate_star').each( function(img) {
            var m = img.id.match(/([0-9]+)_([0-9]+)$/);
            var id = m[2];
            var num = m[1];
            img.altsrc = null_star || img.src;
            Event.observe(img,'click',me.onRateClick.bindAsEventListener(me,id,num));
            Event.observe(img,'mouseover',me.onRateHover.bindAsEventListener(me,id,num));
            Event.observe(img,'mouseout',me.onRateOff.bindAsEventListener(me,id,num));
        });
    },
    onRateClick: function(event,id,num) {
        var rlabel = $("rate_label_" + id);
        if( rlabel )
            rlabel.innerHTML = str_ratings;

        var hname = $("rate_head_" + id);
        if( hname )
            hname.style.display = 'none';

        var bname = $("rate_edit_" + id);
        if( bname )
            bname.style.display = 'none';

        var dname = "rate_block_" + id;
        var url = home_url + "rate/" + id + "/" + num;
        if( this.return_macro )
            url += q + 'rmacro=' + this.return_macro;
        new Ajax.Updater($(dname),url);

    },
    onRateOff: function(event,id,num) {
        var i;
        for( i=1; i<6; i++)
        {
            var img = $('rate_star_' + num + '_' + id);
            img.src = img.altsrc;
        }
    },
    onRateHover: function(event,id,num) {
        var i;
        for( i=1; i<=num; i++)
        {
            var img = $('rate_star_' + num + '_' + id);
            img.src = this.full_star_url;
        }
    }
}