<?

function _t_util_print_prompts($T,&$_TV)
{
    foreach( $_TV['prompts'] as $prompt )
        ?><div class="cc_<?= $prompt['name'] ?>"><?= $T->String($prompt['value']) ?></div><?
}


function _t_util_print_html_content($T,&$A)
{
    if( empty($A['html_content']) )
        return;

    foreach( $A['html_content'] as $html )
        eval( '?>' . $html); //print $html;
}

function _t_util_print_forms($T,&$A)
{
    if( empty($A['forms']) )
        return;

    foreach( $A['forms'] as $form_info )
    {
        $form = $form_info[1];
        if( !empty($form['string_files']) )
        {
            foreach($form['string_files'] as $file )
            {
                $path = $T->Search($file);
                if( empty($path) )
                    die("Can't find string file: $file");
                require_once($path);
            }
        }
        $A['curr_form'] = $form;
        $T->Call($form_info[0]);
    }
}

function _t_util_hide_upload_form($T,&$A)
{
    $msg = str_replace("\n", ' ', addslashes($GLOBALS['str_uploading_msg']));
    
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
        //Event.observe(form_id,'submit', this.dull_screen.bindAsEventListener(this) );
    },

    dull_screen: function()
    { 
         new Insertion.Before( $('banner'), '<div id="bodymask"><span>uploading...</span><div id="maskmsg"><?= $msg ?><?= $spinner ?></div>' );
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


function _t_util_print_bread_crumbs($T,&$_TV)
{
    if( empty($_TV['bread_crumbs']) )
        return;

    ?><div  class="cc_breadcrumbs"><?

    $carr103 = $_TV['bread_crumbs'];
    $cc103= count( $carr103);
    $ck103= array_keys( $carr103);
    for( $ci103= 0; $ci103< $cc103; ++$ci103)
    { 
       $_TV['crumb'] = $carr103[ $ck103[ $ci103 ] ];
       
    if ( !($ci103 == ($cc103-1)) ) {

    ?><a  href="<?= $_TV['crumb']['url']?>"><span ><?= $_TV['crumb']['text']?></span></a>  &raquo; <?
    } // END: if

    if ( $ci103 == ($cc103-1) ){

    ?><span ><?= $_TV['crumb']['text']?></span><?
    } // END: if
    } // END: for loop

    if ( !empty($_TV['crumb_tags'])) {

    ?><select  onchange="document.location = this.options[this.selectedIndex].value;" style="font-size:smaller;">
    <?

    $carr104 = $_TV['crumb_tags'];
    $cc104= count( $carr104);
    $ck104= array_keys( $carr104);
    for( $ci104= 0; $ci104< $cc104; ++$ci104)
    { 
       $_TV['tagopt'] = $carr104[ $ck104[ $ci104 ] ];
       
    if ( !empty($_TV['tagopt']['selected'])) {

    ?><option  selected="selected" value="<?= $_TV['tagopt']['url']?>"><?= $_TV['tagopt']['text']?></option><?
    } // END: if

    if ( !($_TV['tagopt']['selected']) ) {

    ?><option  value="<?= $_TV['tagopt']['url']?>"><?= $_TV['tagopt']['text']?></option><?
    } // END: if
    } // END: for loop

    ?></select>
    <?
    } // END: if

    ?></div>
<?
} // END: function show_bread_crumbs


function _t_util_print_client_menu($T,&$_TV)
{
    $items = $_TV['client_menu'];
    $count = count($items);
    $K = array_keys($items);
    if( !empty($_TV['client_menu_help']) )
        print "<div class=\"client_menu_help\">{$_TV['client_menu_help']}</div>\n";

    print "<ul class=\"client_menu\">\n";
    for( $i = 0; $i < $count; $i++ )
    {
        $I =& $items[ $K[$i] ];
        print "<li><a href=\"{$I['action']}\"><span>${I['menu_text']}</span></a>\n";
        if( !empty($I['help']) )
            print "<span class=\"hint\">{$I['help']}</span>\n";
    }
    print "</ul>";

    if( !empty($_TV['client_menu_hint']) )
        print "<div class=\"client_menu_hint\">{$_TV['client_menu_hint']}</div>\n";
}

function _t_util_prev_next_links($T,&$_TV) 
{
    print '<table  id="cc_prev_next_links"><tr >';

    if ( !empty($_TV['prev_link'])) 
        print "<td ><a href=\"{$_TV['prev_link']}\"><span >{$_TV['back_text']}</span></a></td>\n";

    print '<td  class="cc_list_list_space">&nbsp</td>';

    if ( !empty($_TV['next_link'])) 
        print "<td ><a href=\"{$_TV['next_link']}\"><span >{$_TV['more_text']}</span></a></td>\n";

    print '</tr></table>';

} // END: function prev_next_links


?>
