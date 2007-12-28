<div >
<h1 >A Cappella Browser</h1>
<link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/playlist.css') ?>" title="Default Style"></link>
<link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/info.css') ?>" title="Default Style"></link>
<style >

#type_picker_container, #bpm_picker_container, #limit_picker_container, #lic_picker  {
  float: left;
  margin-right: 11px;
}

#type_picker, #bpm_picker, #limit_picker, #lic_picker {
  font-size: 11px;
}

#hide_container {
  float: left;
  margin-left: 8px;
  white-space: nowrap;
}

#browser_head {
  height: 25px;
  padding: 0px;
  margin: 8px;
}

#featured {
  float: right;
  width: 28%;
  border: 1px solid black;
  padding: 8px;
  background-color: #DDD;
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
}

.featured_pell {
  border: 1px solid black;
  background-color: white;
  padding: 4px;
  margin-bottom: 5px;
}

.feat_avatar {
  float: left;
  margin: 4px;
}

.feat_title {
  font-weight: bold;
  font-size: 14px;
  color: #FF4444;
}

.feat_user {
  display: block;
  margin: 8px;
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
<div  id="type_picker_container">
    Type: <select  id="type_picker"></select>
</div>
<div  id="bpm_picker_container">
    BPM: <select  id="bpm_picker"></select>
</div>
<div  id="limit_picker_container">
    Display: <select  id="limit_picker"></select>
</div>
<div  id="lic_picker_container">
<select  id="lic_picker"></select>
</div>
<div  id="hide_container"><input  type="checkbox" id="hide_remixed"></input> Hide remixed pells</div>
</div>
<div  id="featured">
<?
$A['feats'] = cc_query_fmt('tags=acappella+featured&limit=3&rand=1&dataview=info_avatar');

?><div  class="featured_info">
<span  style="display:none">wrong spellings: acappela, accapella, acapella</span>
<img  src="http://creativecommons.org/images/public/somerights20.gif" style="margin: 8px;float:right" />
All a cappellas are under a <a  href="http://creativecommons.org">Creative Commons</a> license. Please
verify which license applies to each a cappella.
        </div>
<h3 >Featured A Cappellas</h3>
<?

foreach( $A['feats'] as $R )
{
?><div  class="featured_pell">
<div  class="feat_avatar">
<a  href="<?= $R['artist_page_url']?>"><img  src="<?= $R['user_avatar_url']?>" /></a>
</div>
<a  class="feat_title cc_file_link" href="<?= $R['file_page_url']?>"><?= $R['upload_name']?></a>
<a  class="feat_user cc_user_link" href="<?= $R['artist_page_url']?>"><?= $R['user_real_name']?></a>
<div  class="tdc" style="float:right;width:20px"><a  class="info_button" id="_plinfo_<?= $R['upload_id']?>">
</a></div>
<a  href="<?= $R['license_url']?>" about="" rel="license" title="<?= $R['license_name']?>">
<img  src="<?= $R['license_logo_url'] ?>" /></a>
<div  class="clear_me">&nbsp;</div>
<? if( !empty($R['fplay_url']) ) { ?>
<table  cellspacing="0" cellpadding="0" style="width:100%"><tr ><td  style="width:30px">Play:</td><td ><a  class="cc_player_button cc_player_hear" id="_ep_<?= $R['upload_id']?>" href="<?= $R['fplay_url']?>">
</a></td></tr></table>
<? } ?>
</div>
<?
} // END: for loop

?></div>
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
<script  src="<?= $T->URL('/js/info.js') ?>"></script>
<script  src="<?= $T->URL('/js/playlist.js') ?>"></script>
<?
$T->Call('playerembed.xml/eplayer');

?><script type="text/javascript">
//<!--
var feat_playlistMenu = new ccPlaylistMenu( { autoHook: false } );
feat_playlistMenu.hookElements($('featured_pell'));

ccPellBrowser = Class.create();

ccPellBrowser.prototype = {

    allTypes: [ [ 'acappella+melody', 'Melody' ], 
                [ 'acappella+rap', 'Rap' ],
                [ 'acappella+spoken_word', 'Spoken Word' ],
                [ 'acappella+featured', 'Featured' ],
                [ 'acappella', 'All ' ] ],
    currOffset: 0,
    currType: 'acappella+melody',
    totalCount: 0,
    limit: 0,
    hideRemixed: 0,
    query: '',
    currBPM: '-',

    initialize: function(options) {
        this.options = Object.extend( { autoHook: true }, options || {} );
        this.hookElements();
    },

    hookElements: function() {
      var url = home_url + 'samples/lics/acappella';
      this.transport = new Ajax.Request( url, { method: 'get', onComplete: this.hookElements2.bind(this) } );
    },

    hookElements2: function(resp,lics) {
      var lic_picker = $('lic_picker');
      var i = 0;
      lics.each( function(t) {
        lic_picker.options[i++] = new Option( t['text'], t['value']);
      });
      Event.observe( lic_picker, 'change', this.refreshCount.bindAsEventListener( this ) );

      Event.observe( $('browser_prev'), 'click', this.onPrevClick.bindAsEventListener( this ) );
      Event.observe( $('browser_next'), 'click', this.onNextClick.bindAsEventListener( this ) );
      Event.observe( $('hide_remixed'), 'click', this.onHideRemixedClick.bindAsEventListener( this ) );

      var type_picker = $('type_picker');
      i = 0;
      this.allTypes.each( function(t) {
        type_picker.options[i++] = new Option( t[1], t[0] );
      } );
      Event.observe( type_picker, 'change', this.onTypeClick.bindAsEventListener( this ) );

      i = 0;
      var b1, b2, p1, p2;
      var bpm_picker = $('bpm_picker');
      bpm_picker.options[i++] = new Option( 'All', '-' );
      bpm_picker.options[i++] = new Option( 'Below 60', 'bpm_below_60' );
      for( b1 = 60; b1 < 180; b1 += 5 )
      {
        b2 = b1 + 5;
        p1 = b1 < 100 ? '0' + b1 : b1;
        p2 = b2 < 100 ? '0' + b2 : b2;
        bpm_picker.options[i++] = new Option( ' ' + b1 + '-' + (b1+4), 'bpm_' + p1 + '_' + p2);
      }
      bpm_picker.options[i++] = new Option( 'Above 180', 'bpm_above_180' );
      Event.observe( bpm_picker, 'change', this.onBPMClick.bindAsEventListener( this ) );

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

    onBPMClick: function() {
        var bpm_picker = $('bpm_picker');
        this.currBPM = bpm_picker.options[bpm_picker.selectedIndex].value;
        this.refreshCount();
    },

    onHideRemixedClick: function() {
      this.refreshCount();
    },

    changeType: function() {
        var type_picker = $('type_picker');
        var type = type_picker.options[type_picker.selectedIndex].value;
        this.currType = type;
        this.refreshCount();
    },

    getQuery: function() {
        this.query = 'tags=' + this.currType;
        if( this.currBPM != '-' )
            this.query += '+' + this.currBPM;
        var lic_picker = $('lic_picker');
        if( lic_picker.selectedIndex > 0 )
            this.query += '+' + lic_picker.options[ lic_picker.selectedIndex ].value;
        var checkbox = $('hide_remixed');
        this.hideRemixed = checkbox.checked ? 1 : 0;
          if( this.hideRemixed )
              this.query += '&remixmax=0';
    },

    refreshCount: function() {
        this.clearUI();
        var limit_picker = $('limit_picker');
        this.limit = limit_picker.options[limit_picker.selectedIndex].value;
        this.currOffset = 0;
        this.getQuery();
        var url = query_url + this.query + '&f=count';
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
    },

    refreshContent: function() {
        this.clearUI();
        var url = query_url + this.query + '&f=html&t=reccby&limit='+this.limit+'&offset='+this.currOffset;
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

new ccPellBrowser();
//-->
</script>
</div>