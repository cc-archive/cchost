<?

global $_TV;

function _t_basic_stuff_print_prompts()
{
    global $_TV;

    foreach( $_TV['prompts'] as $prompt )
        ?><div class="cc_<?= $prompt['name'] ?>"><?= $prompt['value'] ?></div><?
}

function _t_basic_stuff_print_bread_crumbs()
{
   global $_TV;

    ?><div  class="cc_breadcrumbs">
    <?

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


function _t_basic_stuff_print_client_menu()
{
    global $_TV;

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

function _t_basic_stuff_prev_next_links() 
{
    global $_TV;

    print '<table  id="cc_prev_next_links"><tr >';

    if ( !empty($_TV['prev_link'])) print "<td ><a href=\"{$_TV['prev_link']}\"><span >{$_TV['back_text']}</span></a></td>\n";
    print '<td  class="cc_list_list_space">&nbsp</td>';
    if ( !empty($_TV['next_link'])) print "<td ><a href=\"{$_TV['next_link']}\"><span >{$_TV['more_text']}</span></a></td>\n";

    print '</tr></table>';

} // END: function prev_next_links


?>