ccReccommendBrowser = Class.create();

ccReccommendBrowser.prototype = {

    currOffset: 0,
    totalCount: 0,
    limit: 0,
    query: '',

    initialize: function(options) {
        this.options = Object.extend( { autoHook: true }, options || {} );
        this.hookElements();
    },

    play_in_popup: function()
    {
      this.getQuery();
      var url = home_url + 'playlist/popup' + q + this.query + '&limit='+this.limit+'&offset='+this.currOffset;
      var dim = "height=300,width=550";
      var win = window.open( url, 'cchostplayerwin', "status=1,toolbar=0,location=0,menubar=0,directories=0," +
                    "resizable=1,scrollbars=1," + dim );
    },

    stream_list: function()
    {
      this.getQuery();
      var url = home_url + 'stream/page/playlist.m3u' + q + this.query + '&limit='+this.limit+'&offset='+this.currOffset;
      var link = $('mi_stream_page');
      link.href = url;
      link.click();
    },

    hookElements: function() {
      Event.observe( $('browser_prev'), 'click', this.onPrevClick.bindAsEventListener( this ) );
      Event.observe( $('browser_next'), 'click', this.onNextClick.bindAsEventListener( this ) );
      Event.observe( $('mi_play_page'), 'click',    this.play_in_popup.bindAsEventListener( this ) );
      Event.observe( $('mi_stream_page'), 'click',  this.stream_list.bindAsEventListener( this ) );

      i = 0;
      var limit_picker = $('limit_picker');
      limit_picker.options[i++] = new Option( '5', '5' );
      limit_picker.options[i++] = new Option( '10', '10' );
      limit_picker.options[i++] = new Option( '15', '15' );
      limit_picker.options[i++] = new Option( '25', '25' );
      limit_picker.options[i++] = new Option( '50', '50' );
      limit_picker.selectedIndex = 3;
      Event.observe( limit_picker, 'change', this.onLimitClick.bindAsEventListener( this ) );

      this.refreshCount();
    },

    onLimitClick: function() {
        var limit_picker = $('limit_picker');
        this.limit = limit_picker.options[limit_picker.selectedIndex].value;
        this.refreshCount();
    },

    getQuery: function() {
        this.query = 'reccby=' + ruser;
    },

    refreshCount: function() {
        this.clearUI();
        var limit_picker = $('limit_picker');
        this.limit = limit_picker.options[limit_picker.selectedIndex].value;
        this.currOffset = 0;
        this.getQuery();
        var url = query_url + this.query + '&f=count&datasource=ratings&dataview=count_ratings';
         $('debug').innerHTML = '<a href="' + url + '">' + url + '</a>';
        this.transport = new Ajax.Request( url, { method: 'get', onComplete: this.fillCount.bind(this) } );
    },

    fillCount: function( resp ) {
        this.totalCount = eval(resp.responseText)[0];
        if( this.totalCount > 0 )
        {
          this.refreshContent();
        }
        else
        {
          $('browser').innerHTML = str_no_records_match;
        }
    },

    clearUI: function() {
        $('browser_prev').style.display = 'none';
        $('browser_next').style.display = 'none';        
        $('browser').innerHTML = '...';
        $('feed_links').style.display = 'none';
        $('play_link_container').style.display = 'none';
        $('stream_link_container').style.display = 'none';
    },

    refreshContent: function() {
        this.clearUI();
        var url = query_url + this.query + '&f=html&t=reccby&limit='+this.limit+'&offset='+this.currOffset;
         $('debug').innerHTML = '<a href="' + url + '">' + url + '</a>';
        this.transport = new Ajax.Request( url, { method: 'get', onComplete: this.fillContent.bind(this) } );
    },

    fillContent: function( resp ) {
      try
      {
        var browser = $('browser');
        browser.innerHTML = resp.responseText;
        ccEPlayer.hookElements(browser);
        if( !this.playlistMenu )
            this.playlistMenu = new ccPlaylistMenu( { autoHook: false, playlist: this } );

        // hook the menus, info button, et. al.
        this.playlistMenu.hookElements(browser);

        var prev_mode = this.currOffset > 0 ? 'block' : 'none';
        var next_mode = (this.totalCount - this.limit) > this.currOffset ? 'block' : 'none';
        $('browser_prev').style.display = prev_mode;
        $('browser_next').style.display = next_mode;
        $('feed_links').style.display = 'block';
        $('play_link_container').style.display = 'block';
        $('stream_link_container').style.display = 'block';
        $('mi_stream_page').href = 'javascript://stream';

        var feed_url = query_url + 'f=rss&limit=15&' + this.query; 
        $('rss_feed').href = feed_url;

        //var head = document.getElementsByTagName('HEAD')[0];
      }
      catch (err)
      {
        alert(err);
      }
    },

    onPrevClick: function() {
        this.currOffset -= parseInt(this.limit);
        this.refreshContent();
    },

    onNextClick: function() {
      this.currOffset += parseInt(this.limit);
      this.refreshContent();
    },

    onTypeClick: function( e ) {
      this.changeType();
    }
}

