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
        Modalbox.show( href, {title: thetitle, width: 700, height: 550} );
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

var userHookup = Class.create();

userHookup.prototype = {

    initialize: function(req,params) {
        var url = home_url + 'user_hook/' + req + q + params;
        if( $('debug') )
            $('debug').innerHTML = '<a href="' + url + '">' + url + '</a>';
        new Ajax.Request( url, { method: 'get', onComplete: this.onUserHooks.bind(this) } );
    },

    onUserHooks: function(resp,json) {
        if( json && json.ok_to_rate.length )
        {
            if( json.rate_mode == 'rate' )
            {
                new ratingsHooks(json.ok_to_rate);
            }
            else if( json.rate_mode == 'recommend' )
            {
                new recommendsHooks(json.ok_to_rate);
            }
            else
            {
                alert('error: unknown rate mode: ' + json.rate_mode );
            }
        }
    }

}

var ratingsHooks = Class.create();

ratingsHooks.prototype = {

    full_star_url: null,
    null_star_url: null,
    return_macro: null,
    initialize: function(ok_to_rate) {
        try
        {
            this.full_star_url = full_star;
            this.null_star_url = null_star;
            this.return_macro = rate_return_t || null;
            var me = this;
            $$('.rate_star').each( function(img) {
                var m = img.id.match(/([0-9]+)_([0-9]+)$/);
                var id = m[2];
                var num = m[1];
                if( ok_to_rate.include(id) )
                {
                    img.altsrc = img.src;
                    Event.observe(img,'click',me.onRateClick.bindAsEventListener(me,id,num));
                    Event.observe(img,'mouseover',me.onRateHover.bindAsEventListener(me,id,num));
                    Event.observe(img,'mouseout',me.onRateOff.bindAsEventListener(me,id,num));
                }
            });
        }
        catch (e)
        {
            alert(e);
        }
    },
    onRateClick: function(event,id,num) {
        var rlabel = $("rate_label_" + id);
        if( rlabel )
            rlabel.innerHTML = str_ratings;

        var h_elem = $("rate_head_" + id);
        if( h_elem )
            h_elem.style.display = 'none';

        var b_elem = $("rate_edit_" + id);
        if( b_elem )
            b_elem.style.display = 'none';

        var d_elem = $("rate_block_" + id);
        d_elem.innerHTML = '...';
        var url = home_url + "rate/" + id + "/" + num;
        if( this.return_macro )
            url += q + 'rmacro=' + this.return_macro;
        if( $('debug') )
            $('debug').innerHTML = '<a href="' + url + '">' + url + '</a>';
        new Ajax.Updater(d_elem,url);

    },
    onRateOff: function(event,id,num) {
        var i;
        for( i=1; i<6; i++)
        {
            var img = $('rate_star_' + i + '_' + id);
            img.src = img.altsrc;
        }
    },
    onRateHover: function(event,id,num) {
        var i;
        for( i=1; i<=num; i++)
        {
            var img = 'rate_star_' + i + '_' + id;
            $(img).src = this.full_star_url;
        }
    }
}

var recommendsHooks = Class.create();

recommendsHooks.prototype = {

    return_macro: null,

    initialize: function(ok_to_rate) {
        try
        {
            var me = this;
            this.return_macro = recommend_return_t || null ;
            $$('.recommend_block').each( function(e) {
                var id = e.id.match(/[0-9]+$/);
                if( ok_to_rate.include(id) ) {
                    var html = e.innerHTML;
                    var newHtml = '<span class="recommend_link">' + html + '</span>';
                    e.innerHTML = newHtml;
                    Event.observe(e,'click',me.onRecommendClick.bindAsEventListener(me,id));
                }
            });
        }
        catch (e)
        {
            alert(e);
        }
    },

    onRecommendClick: function(event,id) {
        var d_elem = $("recommend_block_" + id);
        d_elem.innerHTML = '...';
        var url = home_url + "rate/" + id + "/5";
        if( this.return_macro )
            url += q + 'rmacro=' + this.return_macro;
        new Ajax.Updater(d_elem,url);
    }
}
