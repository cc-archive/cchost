<?if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

function _t_recommends_init($T,&$targs) {
    $T->CompatRequired();
}?><div >
<h1 >Recommends Browser</h1>
<link  rel="stylesheet" type="text/css" href="<?= $A['root-url']?>cctemplates/playlist.css" title="Default Style"></link>
<link  rel="stylesheet" type="text/css" href="<?= $A['root-url']?>cctemplates/detail.css" title="Default Style"></link>
<style >

#limit_picker_container, #stream_link_container, #play_link_container {
  float: left;
  margin-right: 11px;
  width: 140px;
}

#limit_picker {
  font-size: 11px;
}

#browser_head {
  height: 25px;
  padding: 0px;
  margin: 8px;
}

.cc_playlist_item {
  width: 320px;
}


#featured {
  float: right;
  width: 160px;
  border: 1px solid black;
  padding: 8px;
  background-color: #DDD;
  overflow: hidden;
}

.cc_playlist_item {
  width: 290px;
}

#featured h3 {
  font-family: Verdana;
  text-align: center;
  letter-spacing: 0.3em;
  margin: 0px 0px 3px 0px;
  font-variant: small-caps;
  font-size: 13px;
}

.featured_info {
  border: 1px solid black;
  background-color: #DDF;
  padding: 7px;
  margin-bottom: 5px;
  overflow:hidden;
}

.clear_me {
  clear: left;
}

.browse_prevnext {
  float: left;
  margin-right: 10px;
}

#feed_links {
  margin: 2px 0px 10px 0px;
  clear: left;
}

#bottom_breaker {
  clear: right;
  margin-bottom: 4px;
}

</style>
<div  id="browser_head">
<div  id="limit_picker_container">
    Display: <select  id="limit_picker"></select>
</div>
<div  class="cc_stream_page_link" id="stream_link_container" style="display:none;">
<a  href="javascript://stream" id="stream_link"><span ><?= $GLOBALS['str_stream'] ?></span></a></div>
<div  class="cc_stream_page_link" id="play_link_container" style="display:none;">
<a  href="javascript://play win" id="play_link"><span ><?= $GLOBALS['str_play'] ;?></span></a></div>
</div>
<div  id="featured">
<h3 ><?= sprintf($GLOBALS['str_recommended_by_s'],$A['get']['fullname']);?></h3>
<div  class="featured_info"><?= sprintf($GLOBALS['str_s_recommends'] ,$A['get']['fullname']);?></div>
</div>
<div  id="browser">
  getting data...
</div>
<div  id="q"></div>
<table  id="cc_prev_next_links"><tbody ><tr >
<td  class="cc_list_list_space">&nbsp;</td>
<td ><a  id="browser_prev" class="cc_gen_button  browse_prevnext" style="display:none" href="javascript://browser_prev"><span >&lt;&lt;&lt; Prev</span></a>
</td>
<td ><a  id="browser_next" class="cc_gen_button  browse_prevnext" style="display:none" href="javascript://browser_next"><span >More &gt;&gt;&gt;</span></a></td>
</tr></tbody></table>
<div  id="feed_links" style="display:none">
<span  class="cc_feed_link">
<a  id="rss_feed" class="cc_feed_button" type="application/rss+xml" href="" title="RSS 2.0">RSS </a>
<span  id="feed_name"></span>
</span>
</div>
<div  id="bottom_breaker">&nbsp;</div>
<script  src="<?= $A['root-url']?>cctemplates/js/playlist.js"></script>
<?$T->Call('playerembed.xml/eplayer');
?><script >
//<!--

var ruser = '<?= $A['get']['ruser']?>';
var fullname = '<?= $A['get']['fullname']?>';

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
      var link = $('stream_link');
      link.href = url;
      link.click();
    },

    hookElements: function() {
      Event.observe( $('browser_prev'), 'click', this.onPrevClick.bindAsEventListener( this ) );
      Event.observe( $('browser_next'), 'click', this.onNextClick.bindAsEventListener( this ) );
      Event.observe( $('play_link'), 'click',    this.play_in_popup.bindAsEventListener( this ) );
      Event.observe( $('stream_link'), 'click',  this.stream_list.bindAsEventListener( this ) );

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
        var url = home_url + 'api/query' + q + this.query + '&f=count';
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
          $('browser').innerHTML = 'no records match';
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
        var url = home_url + 'api/query' + q + this.query + 
                  '&f=html&t=embedded_playlist&limit='+this.limit+'&offset='+this.currOffset;
        // $('q').innerHTML = '<a href="' + url + '">' + url + '</a>';
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
        $('stream_link').href = 'javascript://stream';

        var feed_url = home_url + 'api/query' + q + 'f=rss&limit=15&' + this.query; 
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

new ccReccommendBrowser();
//-->
</script>
</div>