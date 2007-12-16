<?

function _t_util_format_signature($T,$A)
{
    print $T->String('str_from'). " <a href=\"{$A['root-url']}\">{$A['site-title']}</a>";
}

function _t_util_patch_stream_links(&$T,&$A)
{
    $stream_fg = $T->URL('images/player/hear-button-fg.gif');
    if( empty($stream_fg) )
    {
        $str = $T->String('str_stream');
        ?>
<script>
$$('.cc_streamlink').each( function(e) { e.innerHTML = '<?=$str?>'; } );
</script>
        <?
    }
    else
    {
        $stream_bg = $T->URL('images/player/hear-button-bg.gif');
        ?>
<style> 
div.cc_list span { display:block; float: left; }
a.cc_streamlink { float: left; width: 23px; height: 23px; display:block; background: url('<?= $stream_bg ?>') top left no-repeat; }
a.cc_streamlink:hover { background: url('<?= $stream_fg ?>') top left no-repeat; text-decoration:none;}
</style>
        <?
    }
}

function _t_util_print_prompts(&$T,&$A)
{
    foreach( $A['prompts'] as $prompt )
    {
        ?><div class="cc_<?= $prompt['name'] ?>"><?= $T->String($prompt['value']) ?></div><?
    }
}


function _t_util_print_html_content(&$T,&$A)
{
    if( empty($A['html_content']) )
        return;

    foreach( $A['html_content'] as $html )
        eval( '?>' . $html); //print $html;
}

function _t_util_print_forms(&$T,&$A)
{
    foreach( $A['forms'] as $form_info )
    {
        $form = $form_info[1];
        $A['curr_form'] = $form;
        $T->Call($form_info[0]);
    }
}

function _t_util_disable_submit_button(&$T,&$A)
{
    ?>
<script> $('form_submit').disabled = true; </script>
    <?
}

function _t_util_hide_upload_form(&$T,&$A)
{
    $msg = str_replace("\n", ' ', addslashes($T->String('str_uploading_msg')));
    
    ?>
<style>
#bodymask {
        position: absolute;
        top: 0px;
        left: 0px;
        width: 100%;
        height: 400%;
        background-color: #999;
        opacity: 0.8;
        z-index: 100;
}
#maskmsg {
    position: absolute;
    top: 0px;
    left: 0px;
    margin: 10%;
    opacity: 1.0; 
    border: solid 2px black;
    background-color: white;
    padding: 5%;
    text-align: center;
    vertical-align: middle;
    z-index: 103;
    font-size: 23px;
    font-weight: bold;
}
</style>
<? 
$spinner = $T->URL('images/spinner.gif');
if( !empty($spinner) )
    $spinner = "<br /><br /><img src=\"{$spinner}\" />";
?>
<script>
var formMask = Class.create();

formMask.prototype = {

    initialize: function()
    {
        Event.observe(form_id,'submit', this.dull_screen.bindAsEventListener(this) );
    },

    dull_screen: function()
    { 
         new Insertion.Before( $('header'), '<div id="bodymask"><span>uploading...</span><div id="maskmsg"><?= $msg ?><?= $spinner ?></div>' );
         Event.observe('bodymask','click',this.killClick.bindAsEventListener(this),true);
         Event.observe('bodymask','keypress',this.killClick.bindAsEventListener(this),true);
         Element.scrollTo('bodymask');
        //Modalbox.show( $('upload_msg'), {title: null, overlayClose: false, width: 400} );
        return true;
    },

    killClick: function(e)
    {
        Event.stop(e);
        return false;
    }
}

var the_formMask = new formMask();
</script>
    <?
}


function _t_util_print_bread_crumbs(&$T,&$A)
{
    if( empty($A['bread_crumbs']) )
        return;

    ?><div  class="cc_breadcrumbs"><?

    $carr103 = $A['bread_crumbs'];
    $cc103= count( $carr103);
    $ck103= array_keys( $carr103);
    for( $ci103= 0; $ci103< $cc103; ++$ci103)
    { 
       $A['crumb'] = $carr103[ $ck103[ $ci103 ] ];
       
    if ( !($ci103 == ($cc103-1)) ) {

    ?><a  href="<?= $A['crumb']['url']?>"><span ><?= $A['crumb']['text']?></span></a>  &raquo; <?
    } // END: if

    if ( $ci103 == ($cc103-1) ){

    ?><span ><?= $A['crumb']['text']?></span><?
    } // END: if
    } // END: for loop

    if ( !empty($A['crumb_tags'])) {

    ?><select  onchange="document.location = this.options[this.selectedIndex].value;" style="font-size:smaller;">
    <?

    $carr104 = $A['crumb_tags'];
    $cc104= count( $carr104);
    $ck104= array_keys( $carr104);
    for( $ci104= 0; $ci104< $cc104; ++$ci104)
    { 
       $A['tagopt'] = $carr104[ $ck104[ $ci104 ] ];
       
    if ( !empty($A['tagopt']['selected'])) {

    ?><option  selected="selected" value="<?= $A['tagopt']['url']?>"><?= $A['tagopt']['text']?></option><?
    } // END: if

    if ( !($A['tagopt']['selected']) ) {

    ?><option  value="<?= $A['tagopt']['url']?>"><?= $A['tagopt']['text']?></option><?
    } // END: if
    } // END: for loop

    ?></select>
    <?
    } // END: if

    ?></div>
<?
} // END: function show_bread_crumbs


function _t_util_print_client_menu(&$T,&$A)
{
    ?><link rel="stylesheet" type="text/css" href="<?= $T->URL('css/client_menu.css'); ?>" title="Default Style" /><?

    if( !empty($A['client_menu_help']) )
    {
        ?><div class="client_menu_help box"><?= $A['client_menu_help'] ?></div><?
    }

    $items = $A['client_menu'];
    $count = count($items);
    $K = array_keys($items);

    ?><ul class="client_menu"><?

    for( $i = 0; $i < $count; $i++ )
    {
        $I =& $items[ $K[$i] ];
        ?><li><a href="<?= $I['action'] ?>"><span><?= $I['menu_text'] ?></span></a> <?
        if( !empty($I['help']) )
        {
            ?> <span class="hint"><?= $T->String($I['help']) ?></span> <?
        }
    }
    
    ?></ul><?

    if( !empty($A['client_menu_hint']) )
    {
        ?><div class="client_menu_hint"><?= $A['client_menu_hint'] ?></div><?
    }
}

function _t_util_prev_next_links(&$T,&$A) 
{
    print '<table  id="cc_prev_next_links"><tr >';

    if ( !empty($A['prev_link'])) 
        print "<td ><a href=\"{$A['prev_link']}\"><span >{$A['back_text']}</span></a></td>\n";

    print '<td  class="cc_list_list_space">&nbsp</td>';

    if ( !empty($A['next_link'])) 
        print "<td ><a href=\"{$A['next_link']}\"><span >{$A['more_text']}</span></a></td>\n";

    print '</tr></table>';

} // END: function prev_next_links


function _t_util_ratings_stars(&$T,&$A)
{
    $R =& $A['record'];
    if( !empty($R['ratings']) )
    {
        foreach( $R['ratings'] as $rsize ) // 'half' 'full'
        {
            $src = 'images/stars/star-' . $rsize . '.gif';
            print '<img style="width:17px;height:17px;margin:0px;" src="' . $T->URL($src) . '" />';
        }
        print ' ' . $R['ratings_score'];
    }
}

function _t_util_ratings_stars_user(&$T,&$A)
{
    $R =& $A['record'];
    if( !empty($R['ratings']) )
    {
        $id = $R['upload_id'];
        if( $A['ajax'] )
            print '<div>'; 
        else
            print '<div id="rate_block_' . $id . '">';
        $i = 1; 
        if( empty($R['ratings']) )
        {
            // there are no existing, put out editable blank stars...

            print $T->String('str_rate') . ': ';
            for( $i = 1; $i < 6; $i++ )
            {
                $url = $T->URL('images/stars/star-empty.gif');
                $info = 'id="rate_star_' . $i . '_' . $id . '" class="rate_star"';
                print '<img '.$info.' style="width:17px;height:17px;margin:0px;" src="' . $url . '" />';
            }
        }
        else
        {
            // there's already a rating, put out stars, editable if legal to do so...

            foreach( $R['ratings'] as $rsize ) // 'half' 'full'
            {
                $src = 'images/stars/star-' . $rsize . '.gif';
                $url = $T->URL($src);
                $info = 'id="rate_star_' . $i . '_' . $id . '" class="rate_star"';
                print '<img '.$info.' style="width:17px;height:17px;margin:0px;" src="' . $url . '" />';
                ++$i;
            }
            print ' ' . $R['ratings_score'];
        }
        print '</div>';
    }
}

function _t_util_ratings_stars_small(&$T,&$A)
{
    $R =& $A['record'];
    if( !empty($R['ratings']) )
    {
        if( $A['ajax'] )
            print '<div>'; 
        else
            print '<div class="small_stars" id="rate_block_' . $R['upload_id'] . '">';
        foreach( $R['ratings'] as $rsize ) // 'half' 'full'
        {
            $src = 'images/stars/star-' . $rsize . '-s.gif';
            $url = $T->URL($src);
            print '<img style="width:10px;height:10px;margin:0px;" src="' . $url . '" />';
        }
        print ' ' . $R['ratings_score'];
        print '</div>';
    }
}

function _t_util_ratings_stars_small_user(&$T,&$A)
{
    $R =& $A['record'];
    if( !empty($R['ratings']) )
    {
        $id = $R['upload_id'];
        if( $A['ajax'] )
            print '<div>'; 
        else
            print '<div class="small_stars" id="rate_block_' . $id . '">';
        $i = 1; 
        if( empty($R['ratings']) )
        {
            // there are no existing, put out editable blank stars...

            print $T->String('str_rate') . ': ';
            for( $i = 1; $i < 6; $i++ )
            {
                $url = $T->URL('images/stars/star-empty-s.gif');
                $info = 'id="rate_star_' . $i . '_' . $id . '" class="rate_star"';
                print '<img '.$info.' style="width:10px;height:10px;margin:0px;" src="' . $url . '" />';
            }
        }
        else
        {
            // there's already a rating, put out stars, editable if legal to do so...

            foreach( $R['ratings'] as $rsize ) // 'half' 'full'
            {
                $src = 'images/stars/star-' . $rsize . '-s.gif';
                $url = $T->URL($src);
                $info = 'id="rate_star_' . $i . '_' . $id . '" class="rate_star"';
                print '<img '.$info.' style="width:10px;height:10px;margin:0px;" src="' . $url . '" />';
                ++$i;
            }
            print ' ' . $R['ratings_score'];
        }
        print '</div>';
    }
}

function _t_util_recommends(&$T,&$A)
{
    $R =& $A['record'];

    if( empty($A['ajax']) )
    {
        ?><div class="rated recommend_block" id="recommend_block_<?= $R['upload_id'] ?>"><?
    }
    
    print $T->String('str_recommends') . ' <span>(' . sprintf('%d',$R['upload_num_scores']) . ')</span>';

    if( empty($A['ajax']) )
    {
      ?></div><?
    }
}

?>
