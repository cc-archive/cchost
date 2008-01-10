<?if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');


function _t_playlist_playlist_create_dyn(&$T,&$A) {
  ?><link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/playlist.css') ?>" title="Default Style"></link>
  <div id="dyn_filter_editor_parent">
<div  id="dyn_filter_editor" class="box" >
<div  id="filter_form" >
</div>
</div>
</div>
<script  src="<?= $T->URL('js/query_filter.js')?>" ></script>
<script  src="<?= $T->URL('js/autocomp.js')?>" ></script>
<script  src="<?= $T->URL('js/autopick.js')?>" ></script>
<script type="text/javascript">
    function submit_callback(event) {
      var qstring = window.filters.queryString() + '&f=playlist';
      var promo_tag = '<?= $A['plargs']['promo_tag']?>';
      if( promo_tag.length > 0 )
        qstring += '&promo_tag=' + promo_tag;
      document.location.href = '<?= $A['plargs']['submit_url']?>' + q + qstring;
    }

    var filters = new ccQueryBrowserFilters( 
        { submit_text: '<?= $T->String($A['plargs']['submit_text']) ?>',
          init_values: <?= $A['plargs']['edit_query']?>,
          reqtags: <?= cc_get_config( 'playlist_tags', 'json' ) ?>,
          onFilterSubmit: submit_callback.bindAsEventListener() } );

  </script>
<?}

function _t_playlist_playlist_popup(&$T,&$A) 
{

    foreach( $A['args']['with'] as $R )
    {  
       ?><span><a href="<?= $A['home-url']?>api/playlist/remove/<?= $A['args']['upload_id']?>/<?= $R['cart_id']?>" class="cc_playlist_menu_item"><span><?= $T->String('str_pl_remove_from')?> <span class="cc_playlist_name"><?= $R['cart_name']?></span></span></a></span><?
    }

    foreach( $A['args']['without'] as $R )
    { 
     ?><span><a href="<?= $A['home-url']?>api/playlist/add/<?= $A['args']['upload_id']?>/<?= $R['cart_id']?>" class="cc_playlist_menu_item"><span><?= $T->String('str_pl_add_to')?> <span class="cc_playlist_name"><?= $R['cart_name']?></span></span></a></span><?
    }
 
    ?><span><a href="<?= $A['home-url']?>api/playlist/new/<?= $A['args']['upload_id']?>" class="cc_playlist_menu_item cc_playlist_add_mi"><span><?=$T->String('str_pl_add_to_new')?></span></a></span><?
}



function _t_playlist_playlist_list(&$T,&$A) 
{
  ?><table  class="cc_pl_table"><tr><td><?

    if ( !empty($A['args']['menu'])) 
    {
        ?><ul  class="cc_playlist_owner_menu light_bg dark_border"><?
          
        foreach( $A['args']['menu'] as $mi )
        {
?>
            <li>
            <a target="_parent" href="<?= $mi['url']?>" id="<?= $mi['id']?>" class="<?= $mi['class']?>"><span><?= $T->String($mi['text'])?></span></a>
            </li>
<?
        }

        ?></ul><?
    }

    ?></td><td><?
    
    if ( !empty($A['args']['feed_q'])) 
    {
        ?><div class="cc_playlist_feed"><a target="_parent" href="<?= $A['args']['feed_q']?>">
                <img src="<?= $T->URL('images/feed-icon16x16.png') ?>" /></a>
          </div><?
    }


    $playlist = $A['args']['playlist'];
    
?>
    <a target="_parent" href="<?= $A['home-url']?>playlist/browse/<?= $playlist['cart_id']?>">
        <div class="cc_playlist_title"><?= $playlist['cart_name']?></div>
    </a>
    <span class="cc_playlist_date">
        <?= $T->String('str_pl_created_by')?> <a target="_parent" class="cc_user_link" href="<?= $playlist['artist_page_url']?>"><?= $playlist['user_real_name']?></a> on <?= $playlist['cart_date_format']?>
    </span>
<?
    if ( !empty($playlist['cart_desc']) )
    {
?>
        <div  class="gd_description" id="pldesc_<?= $playlist['cart_id']?>">
            <div style="padding: 10px;"><?= cc_format_text($playlist['cart_desc']);?></div>
        </div>
<?
    }
    if ( !empty($playlist['cart_msgs']) )
    {
?>
        <div  class="gd_description" id="pldesc_<?= $playlist['cart_id']?>">
            <div  style="padding: 10px;">
                <? foreach( $playlist['cart_msgs'] as $cmsg )
                   {
                        print $T->String($cmsg);
                        print '<br /><br />';
                   }
                ?>
            </div>
        </div>
<?
    }
    
?>  </td></tr>
    </table>
    <div  class="cc_pl_div" id="_cart_<?= $playlist['cart_id']?>">
<?
    $A['records'] = $A['args']['records'];
    $T->Call('playlist_list_lines');
    
    ?></div><?

    if ( !empty($A['is_logged_in'])) {
        ?><span id="pl_user_<?= $playlist['cart_id']?>"></span><?
    }

    if ( !empty($A['args']['is_owner'])) {
        ?><span id="pl_owner_<?= $playlist['cart_id']?>"></span><?
    } 

    ?><div style="clear:right">&nbsp;</div><?
}


function _t_playlist_playlist_list_window_cart(&$T,&$A) 
{
?>
    <div  class="cc_playlist_popup_window">
    <table  class="cc_pl_table" cellpadding="0" cellspacing="0"><tr><td>
<?
    if ( !empty($A['args']['menu'])) 
    {
?>
        <ul class="cc_playlist_owner_menu light_bg dark_border"><?
            
        foreach( $A['args']['menu'] as $mi )
        {
?>
            <li >
                <a target="_parent" href="<?= $mi['url']?>" id="<?= $mi['id']?>" class="<?= $mi['class']?>">
                    <span><?= $T->String($mi['text']) ?></span></a>
            </li>
<?
        }

        ?></ul><?
    }
    
    ?></td>
    <td>
        <? $A['R'] = $A['args']['playlist']; ?>
        <a target="_parent" href="<?= $A['home-url']?>playlist/browse/<?= $A['R']['cart_id']?>">
            <div  class="cc_playlist_title"><?= $A['R']['cart_name']?></div>
        </a>
        <span class="cc_playlist_date">
            <?= $T->String('str_pl_created_by')?> 
            <a target="_parent" class="cc_user_link" href="<?= $A['R']['artist_page_url']?>"><?= $A['R']['user_real_name']?></a>
            <br  /><?= $A['R']['cart_date_format']?>
        </span>
    </td></tr>
    <tr><td></td><td  style="height:22px;">
        <div  class="cc_playlist_pcontainer" id="plc_id"></div>
    </td></tr>
    </table>
    <table  class="cc_pl_table">
<?
 
    foreach( $A['args']['records'] as $R )
    {    
?>
        <tr>
        <td class="cc_playlist_item" id="_pli_<?= $R['upload_id']?>">
<?
            $iname = CC_strchop($R['upload_name'],30);
            $uname = CC_strchop($R['user_real_name'],22);
?>
            <span><a target="_parent" class="cc_file_link" href="<?= $R['file_page_url']?>"><?= $iname?></a></span> 
            <?= $T->String('str_by')?>
            <a target="_parent" class="cc_user_link" href="<?= $R['artist_page_url']?>"><?= $uname ?></a>
        </td>
<?
            if ( !empty($R['fplay_url'])) 
            {
?>
                <td  class="cc_playlist_pcontainer">
                  <a class="cc_player_button cc_player_hear" 
                  id="_ep_<?= $A['R']['cart_id']?>_<?= $R['upload_id']?>" href="<?= $R['fplay_url']?>"></a>
                </td>
<?
            }

            ?></tr><?
    }

    ?></table><?

    if ( !empty($A['is_logged_in']) )
    {
        ?><span id="pl_user_<?= $A['R']['cart_id']?>"></span><?
    }
    if ( !empty($A['args']['is_owner']) )
    {
        ?><span id="pl_owner_<?= $A['R']['cart_id']?>"></span><?
    }
        
    ?></div><?
}

function _t_playlist_playlist_popup_window(&$T,&$A) 
{
    ?><link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/playlist.css') ?>" title="Default Style"></link>
    
    <? 
        $A['player_options'] = 'autoHook: false,showVolume: false,showProgress: false,plcc_id: \'plc_id\''; 
    ?>
<script  src="<?= $T->URL('/js/info.js') ?>"></script>
<script  src="<?= $T->URL('js/playlist.js') ?>" ></script>
<?
    $T->Call('playlist.tpl/playlist_list_window_cart');
    $T->Call('playerembed.xml/eplayer');
?>
<script type="text/javascript">
    new ccPlaylistMenu();
    new ccPagePlayer(<?= $A['args']['playlist']['cart_id']?>);
    new ccParentRedirector(); 
</script>
<style type="text/css">
  div.cc_playlist_popup_window,
  #cc_wrapper1, #cc_wrapper2
  #cc_content, #cc_centercontent {
     width: auto;
  }
  #plc_id {
    width: 250px;
  }
  body {
    margin: 5px;
  }
  h1 { display: none; } /* don't ask */
</style>
<?
}

function _t_playlist_playlist_show_browser(&$T,&$A) 
{
?>
<link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/playlist.css') ?>"  title="Default Style"></link>
<link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/info.css') ?>"  title="Default Style"></link>
<script  src="<?= $T->URL('/js/info.js') ?>"></script>
<script  src="<?= $T->URL('js/playlist.js') ?>" ></script>
<div  id="playlist_browser" class="grid"><span><?= $T->String('str_pl_getting') ?>...</span></div>
<? $A['player_options'] = 'autoHook: false';$T->Call('playerembed.xml/eplayer'); ?>
<script type="text/javascript">
    var plb = new ccPlaylistBrowser( 'playlist_browser', <?= $A['args']?> );
</script>
<?}

function _t_playlist_playlist_menu(&$T,&$A) 
{
?>
<link  rel="stylesheet" type="text/css" href="<?= $T->URL('css/playlist.css') ?>" title="Default Style"></link>
<script  src="<?= $T->URL('/js/info.js') ?>"></script>
<script  src="<?= $T->URL('js/playlist.js') ?>" ></script>
<script type="text/javascript">
function playlist_hook_menu() {
    new ccPlaylistMenu();
}
</script>
<?
}
?>
