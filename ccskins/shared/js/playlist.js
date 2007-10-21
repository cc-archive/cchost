/*
* Creative Commons has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file LICENSE.TXT.
* 
* You may use the ccHost software in accordance with the
* terms of that license. You agree that you are solely 
* responsible for your use of the ccHost software and you
* represent and warrant to Creative Commons that your use
* of the ccHost software will comply with the CC-GNU-GPL.
*
* $Id$
*
*/

var ccThinking = {

    currX: 0,
    currY: 0,
    div: null,
    enabled: false,

    Enable: function(event) {
        this.currY  = (Event.pointerY(event) - 5); 
        this.currX  = (Event.pointerX(event) + 15); 
        this.enabled = true;
    },

    onCreate: function(){
        if( !this.enabled )
            return;

        if( !this.div )
        {
            this.div = document.createElement('div');
            this.div.style.display = 'none';
            this.div.className = 'cc_playlist_thinking';
            this.div.innerHTML = 'thinking...';
            document.body.appendChild(this.div);
        }
        this.div.style.top  = this.currY + 'px';
        this.div.style.left = this.currX + 'px';
        this.div.style.display = 'block';
    },

    _hide_popup: function() {
        if( this.div )
            this.div.style.display = 'none';
    },

    onComplete: function(req,t,json) {
        if(Ajax.activeRequestCount == 0) {
            this._hide_popup();
            this.enabled = false;
        }
        else {
            
        }
    }
};

Ajax.Responders.register(ccThinking);

/************************************************
*
*  Playlist popup menu for page listing
*
*************************************************/
ccPlaylistMenu = Class.create();

ccPlaylistMenu.prototype = {

    openMenu: null,
    inOpen: false,
    windowHooked: false,
    openInfo: null,
    transport: null,

    initialize: function(options) {
        this.options = Object.extend( { autoHook: true }, options || {} );
        if( this.options.autoHook )
            this.hookElements();
    },

    hookElements: function(parent) {
        var me = this;
        var found = false;
        $$('.cc_playlist_button', parent).each( function(e) {
            var id = e.href.match( /([0-9]+$)/ )[1];
            Event.observe( e, 'click', me.onMenuButtonClick.bindAsEventListener( me, id ) );
            found = true;
        });
        $$('.cc_playlist_i',parent).each( function(pli) {
            var upload_id = pli.id.match(/[0-9]+$/);
            Event.observe( pli, 'click', me.onInfoClick.bindAsEventListener( me, upload_id ) );
            found = true;
        });

        if( found && !this.windowHooked ) {
            Event.observe( document.body /* window */, 'click', this.onWindowClick.bindAsEventListener(this));
            this.windowHooked = true;
        }

    },

    onMenuButtonClick: function(e,id) {
        if( this.transport )
            return;

        this._close_info();
        var pid = 'pl_menu_' + id;
        if( this.openMenu )
        {
            var openId = this.openMenu.id;
            this._close_menu();
            if( openId == pid )
                return;
        }
        if( $(pid) )
        {
            if( $(pid).needRefresh ) 
            {
                $(pid).style.display = 'block';
                this._refresh_menu( id, pid );
            }
            else
                this._open_menu(pid);
        }
        else
        {
            var element = Event.element(e);
            while( element.tagName != 'A' )
                element = element.parentNode;
            this._create_controls( element, pid, id );
        }
        this.inOpen = true;
        //if( this.options.stopOnClick )
            Event.stop(e);
            return false;
    },

    _create_controls: function( link, pid, id ) {
        var html = '<div id="' + pid + '" style="display:block;opacity:0.7;" class="cc_playlist_popup">getting information... </div>';
        new Insertion.After( link, html );
        var pp = $(pid);
        Position.clone( link, pid, {  setWidth:   false,
                                      setHeight:  false,
                                      offsetTop:  20,
                                      offsetLeft: 20 } );
        this._refresh_menu( id, pid );
    },

    _refresh_menu: function( id, pid ) {
        var url = home_url + 'api/playlist/with/' + id + q + 'f=html&m=playlist_popup'
        this.transport = new Ajax.Request( url, { method: 'get', onComplete: this._req_with.bind(this,pid) } );
    },

    _req_with: function( pid, resp ) {
        try
        {
            var e = $(pid);
            e.needRefresh = false;
            e.innerHTML = resp.responseText;
            var me = this;
            $A(e.getElementsByTagName('A')).each( function( a ) {
                var url = a.href;
                a.href = 'javascript://playlist menu item';
                Event.observe( a, 'click', me.onMenuItemClick.bindAsEventListener( me, url, pid ) );
            });
            this._open_menu(pid);
        }
        catch (err)
        {
            alert(err);
        }
        this.transport = null;
    },

    onMenuItemClick: function( event, url, pid ) {
        var p = $(pid);
        ccThinking.Enable(event);
        this.transport = new Ajax.Request( url, { method: 'get', onComplete: this._req_status.bind(this,pid) } );
        Event.stop( event );
    },

    _req_status: function( pid, resp, json ) {
        try
        {
            var p = $(pid);
            p.needRefresh = true;
            if( json )
            {
                if( json.message )
                {
                    p.innerHTML = json.message;
                    this.onJSONCommand(json);
                }
                else
                {
                    p.innerHTML = json;
                }
            }
            else
            {
                p.innerHTML = resp.responseText;
            }
            new ccDelayAndFade( 1000, p, 0, 1500, 20, { complete: this._close_menu.bind(this) } );
        }
        catch (err)
        {
            alert(err);
        }
        this.transport = null;
    },
    
    _open_menu: function(pid) {
        var pp = $(pid);
        this.openMenu = pp;
        pp.style.display = 'block';
        new ccDelayAndFade( 0, pp, 1, 250, 4 );
    },

    _close_menu: function() {
        if( this.openMenu )
        {
            this.openMenu.style.display = 'none';
            this.openMenu = null;
        }
    },

    onInfoClick: function(event, upload_id ) {
        if( this.transport )
            return;

        this._close_menu();
        var old_id = this._close_info();
        var info_id = '__plinfo__' + upload_id;
        if( old_id == info_id )
            return;

        if( $(info_id) )
        {
            this.openInfo = $(info_id);
            this.openInfo.style.display = 'block';
            this.openInfo.style.width = "auto";
        }
        else
        {
            var url = home_url + 'api/query' + q + 'f=html&t=info&ids=' + upload_id;
            var y = (Event.pointerY(event) + 12), x = (Event.pointerX(event) - 50);
            var html = '<div class="cc_playlist_info" id="' + info_id + '" ' +
                       'style="display:none;position:absolute;height:auto;top:'+y+'px;left:'+x+'px"></div>';
            new Insertion.After(Event.element(event),html);
            ccThinking.Enable(event);
            //var link = '<a href="' + url + '">' + url + '</a>';
            //$(info_id).innerHTML = link; $(info_id).style.display = 'block'; return;
            this.transport = new Ajax.Request( url, { method: 'get', onComplete: this._resp_info.bind(this, info_id ) } );
        }
        this.inOpen = true;
    },

    _close_info: function() {
        if( this.openInfo )
        {
            var old_id = this.openInfo.id;
            this.openInfo.style.display = 'none';
            this.openInfo = null;
            return old_id;
        }
        return '-1';
    },

    _resp_info: function( info_id, resp ) {
        var info = $(info_id);
        //info.style.opacity = 0;
        info.innerHTML = resp.responseText;
        info.style.display = 'block';
        this.openInfo = info;
        var x = (document.body.offsetWidth/2) - (info.offsetWidth/2);
        if( x < 0 )
            x = 100;
        info.style.left = x + 'px';
        //new ccDelayAndFade( 0, info, 1.0, 40, 10); 
        this.transport = null;
    },

    CloseMenus: function() {
        if( this.inOpen )
        {
            this.inOpen = false;
        }
        else
        {
            this._close_menu();
            this._close_info();
        }
    },

    onWindowClick: function(e) {
        this.CloseMenus();
        return true;
    },

    /* 
    *
    * playlist stuff ... doesn't really belong here yet here it is... 
    *
    */

    onJSONCommand: function(json) {
        if( json.command = 'delete' )
        {
            var id = '_ep_' + json.cart_id + '_' + json.upload_id;
            var this_row = $(id);
            if( this_row )
            {
                while( !Element.hasClassName(this_row,'trr') )
                    this_row = this_row.parentNode;
                this_row.remove();
            }
        }
    }
}

/************************************************
*
*  Embedded playler manager
*
*************************************************/

ccPlayerMethods = {
    hook_playlist: function(playlist_id,parent) {
        var play_all = $('_pla_' + playlist_id);
        if( ccEPlayer && ccEPlayer.hookElements )
        {
            var play_ids = ccEPlayer.hookElements(parent);
            if( play_all )
            {
                if( ccEPlayer.flashOK )
                {
                    play_all.href = 'javascript:// play all';
                    var ids = play_ids.inspect();
                    play_all._playlist = ids;
                    ccEPlayer.SetPlaylist(ids,false);
                    Event.observe( play_all, 'click', this.onPlayAll.bindAsEventListener(this) );
                }
                else
                {
                    var ids_str = '';
                    ids_str = play_ids.inject( ids_str, function(str,idarr) {
                        str += idarr[0].match(/[0-9]+$/) + ';';
                        return str;
                    });
                    play_all.href = home_url + 'api/query' + q + 'f=m3u&nosort=1&ids=' + ids_str;
                }
            }
            var play_win = $('_plw_' + playlist_id);
            if( play_win )
            {
                if( ccEPlayer.flashOK )
                    Event.observe( play_win, 'click', this.onPlayWin.bindAsEventListener(this,playlist_id) );
                else
                    play_win.style.display = 'none';
            }
        }
    },

    onPlayWin: function(event,playlist_id) {
        var qs  = location.search.substring(1);
        var url = home_url + 'playlist/popup/' + playlist_id + q + qs;
        var dim = "height=300,width=550";
        // var url = home_url + 'api/query' + q + 't=mplayerbig&f=html&playlist=' + playlist_id + '&' + qs;
        // var dim = "height=170, width=420";
        var win = window.open( url, 'cchostplayerwin', "status=1,toolbar=0,location=0,menubar=0,directories=0," +
                      "resizable=1,scrollbars=1," + dim );
        Event.stop(event);
        win.focus();
    },

    onPlayAll: function(event) {
        var element = Event.element(event);
        while( !element.href )
            element = element.parentNode;
        ccEPlayer.SetPlaylist(element._playlist,true);
        ccEPlayer.StartPlaylist();
        Event.stop(event);
    },

    initialize: function(playlist_id) {
        this.hook_playlist(playlist_id);
    }
}

ccPagePlayer = Class.create();

ccPagePlayer.prototype = Object.extend( {}, ccPlayerMethods );

/************************************************
*
*  Playlist browser
*
*************************************************/

var ccdbg = '';

var ccPlaylistBrowserObject = {

    selected: null,
    openRec: '',
    openingRec: false,
    browsingAway: false,

    /*
       get the list of playlists
    */
    initialize: function(container_id,options) {
        this.options = Object.extend( { }, options || {} );
        this.container_id = container_id;
        this._get_carts();
    },

    _get_carts: function(url) {
        if( !url )
        {
            url = home_url + 'api/playlist/browse';
            if( this.options.user )
                url += '/' + this.options.user;
            url += q + 'f=html&m=playlist_browse';
            if( this.options.upload )
                url += '&upload_id=' + this.options.upload;
            if( this.options.hot )
                url += '&hot=1';
            if( this.options.since )
                url += '&since=' + this.options.since;
        }
        var me = this;
        //var link = '<a href="' + url + '">' + url + '</a>';
        //$(this.container_id).innerHTML = link; return;
        new Ajax.Request( url, { method: 'get', onComplete: me._resp_browse.bind(me) } );
    },

    /*
       got the list of playlists, already formatted in HTML
    */
    _resp_browse: function( resp, json ) {

        try
        {
            $(this.container_id).innerHTML = resp.responseText;

            if( !$(this.container_id)._cc_hooked ) 
            {
                Event.observe( this.container_id, 'mouseover', this.onListHover.bindAsEventListener(this) );
                Event.observe( this.container_id, 'click',     this.onListClick.bindAsEventListener(this) );
                $(this.container_id)._cc_hooked = true;
            }
            var me = this;
            $$('#cc_prev_next_links a').each( function(a) {
                Event.observe( a, 'click', me.onPrevNext.bindAsEventListener(me,a.href) );
                a.href = 'javascript:// prev-next';
            });
        }
        catch (err)
        {
            this._report_error('pl: ',err);
        }
    },

    onPrevNext: function(e,href) {
        //var offs = href.match(/\?(.*)$/)[1];
        this.openRec = '';
        this.openingRec = false;
        this.selected = null;
        this.browsingAway = false;

        this._get_carts(href);
        Event.stop(e);
    },

    onListClick: function(event) {
        if( this.browsingAway )
            this.browsingAway = false;  // have to set this in case user does 'back' 
        else
            this.openPlaylist(this.selected,event);
    },

    openPlaylist: function(id, event) {
        try
        {
            var cart_id = $(id).id.replace('_pl_','');
            var detailId = '_pld_' + cart_id;

            this.openingRec = true;

            if( this.openRec.length > 0 )
            {
                // close the 'current' playlist
                $(this.openRec).style.display = 'none';
            }

            if( $(detailId) )
            {
                var element = $(detailId);

                if( this.openRec == detailId )
                {
                    // all we did was close the currently open one
                    this.openRec = '';
                }
                else
                {
                    // this is a request to open another playlist and
                    // we already have it cached and it's not changed
                    // so just open it...
                    element.style.display = 'block';
                    this.openRec = detailId;
                    // reset sel line because of painting probs
                    Element.removeClassName( this.openRec, 'cc_playlist_sel' );
                }
            
                this.openingRec = false;
            }
            else
            {
                // this is a request to open playlist but we don't have
                // the contents, go to the server to get the playlist
                // details...

                if( event )
                    ccThinking.Enable(event);
                this.openRec = detailId;            
                var html = '\n<div id="'+detailId
                               + '" class="cc_playlist_detail" style="display: none;">getting playlist...</div>\n';
                new Insertion.After(this.selected, html);
                Event.observe( detailId, 'click', this.onStopDetailClick.bindAsEventListener(this, detailId) );
                this.delayDisplay(detailId,'block');
                this.refreshDetails(cart_id);
            }
        }
        catch (err)
        {
            this._report_error( 'open pl:', err);
        }
    },

    delayDisplay: function( id, how ) {
        setTimeout( this.onDelayDisplay.bind(this,id,how), 300 );
    },

    onDelayDisplay: function(id,how) {
        $(id).style.display = how;
    },

    onStopDetailClick: function(event, detailId) {
        var e = Event.element(event);
        var detail = $(detailId);
        while( e != detail )
        {
            if( e.href && e.href.match(/^http:/) )
            {
                this.browsingAway = true;
                return;
            }
            e = e.parentNode;
        }
        if( this.playlistMenu )
            this.playlistMenu.CloseMenus();
        Event.stop(event);
        return false;
    },

    /*
    * Call back to the server to get the playlist information
    */
    refreshDetails: function( cart_id ) {
        var url = home_url + 'api/playlist/view/' + cart_id + q + 'f=html&m=playlist_list&fcac=' + (new Date()).getTime();
        new Ajax.Request( url, { method: 'get', onComplete: this._resp_playlist.bind(this, cart_id) } );
    },

    /*
    * Server responded with playlist info...
    */
    _resp_playlist: function( cart_id, resp, json ) {

        try
        {
            var e = $('_pld_' + cart_id);
            e.innerHTML = resp.responseText;

            // hook the .mp3 links
            this.hook_playlist(cart_id,e);
            
            if( !this.playlistMenu )
                this.playlistMenu = new ccPlaylistMenu( { autoHook: false, playlist: this } );

            // hook the menus, info button, et. al.
            this.playlistMenu.hookElements(e);

            e.style.display = 'block';
        }
        catch (err)
        {
            this._report_error( 'detail: ', err );
        }
        this.openingRec = false;
    },

    onListHover: function(event) {
        var e = Event.element(event);

        if( Element.hasClassName( e, 'cc_playlist_line' ) )
        {
            if( this.selected )
                Element.removeClassName(this.selected, 'cc_playlist_sel' );
            Element.addClassName( e, 'cc_playlist_sel' );
            this.selected = e;
        }
    },

    _report_error: function(cap,err) {
        alert( cap + ' :' + err );
    }
}

ccPlaylistBrowser = Class.create();
ccPlaylistBrowser.prototype =  Object.extend( ccPlayerMethods, ccPlaylistBrowserObject );

/*
    parent redirector
*/
ccParentRedirector = Class.create();

ccParentRedirector.prototype = {

    initialize: function() {
        var me = this;
        $$('.cc_playlist_owner_menu LI A').each( function(a) {
            if( !a.href.match(/^javascript:/) )
                Event.observe(a,'click',me.onClickAndClose.bindAsEventListener(me));
                a.plocation = a.href;
                a.href = 'javascript:// menu option';
        });

        $$('A').each( function(a) {
            if( !a.href.match(/^javascript:/) && !a.plocation )
                Event.observe(a,'click',me.onClick.bindAsEventListener(me));
                a.plocation = a.href;
                a.href = 'javascript:// open link';
        });
    },

    onClickAndClose: function(event) {
        this._click(event,true);
    },

    onClick: function(event) {
        this._click(event,false);
    },

    _click: function(event,doClose) {
        var e = Event.element(event);
        while( !e.plocation )
            e = e.parentNode;
        self.opener.location = e.plocation;
        Event.stop(event);
        if(doClose)
            self.close();
        return false;
    }

}

