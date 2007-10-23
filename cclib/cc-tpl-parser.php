<?


function cc_tpl_parse_var($prefix,$var,$postfix)
{
    $parts = explode('/',$var);
    if( $parts[0]{0} == '#' )
    {
        $v = '$' . substr($parts[0],1);
        array_shift($parts);
        if( empty($parts) )
            return $prefix . $v . $postfix;
    }
    else
    {
        $v = '$A';
    }
    return $prefix . $v . '[\'' . join( "']['", $parts ) . '\']' . $postfix;
}

function cc_tpl_parse_var_check($prefix,$var,$postfix)
{
    $varname = cc_tpl_parse_var('',$var,'');

    return "$prefix empty($varname) ? '' : $varname $postfix";
}

function cc_tpl_parse_loop($arr, $item)
{
    $arr_name = cc_tpl_parse_var('',$arr,'');
    return "<? if( !empty($arr_name) ) foreach( $arr_name as \$$item ) { ?>";
}

function cc_tpl_parse_call_macro($prefix, $mac)
{
    if( ($mac{0} != "'") && ($mac{0} != '$') )
        $mac = '$A[\'' . $mac . '\']';

    return "$prefix \$T->Call($mac); ?>";
}

function cc_tpl_parse_if_null( $is_null, $var )
{
    $varname = cc_tpl_parse_var('',$var,'');
    $bang = $is_null == 'not_' ? '!' : '';
    return "<? if( {$bang}empty($varname) ) { ?>";
}

function cc_tpl_parse_define($left,$right)
{
    $left  = cc_tpl_parse_var('',$left,'');
    if( $right{0} != "'" )
        $right = cc_tpl_parse_var('',$right,'');

    return "<? $left = $right; ?>";
}

function cc_tpl_parse_file($filename,$bfunc)
{
    return cc_tpl_parse_text(file_get_contents($filename),$bfunc);
}

function cc_tpl_parse_chop($prefix,$varname,$amt)
{
    $var = cc_tpl_parse_var('',$varname,'');
    return "$prefix CC_strchop($var,$amt,true); ?>";
}

function cc_tpl_parse_text($text,$bfunc)
{
    static $ttable;

    if( !isset($ttable) )
      $ttable = array(
        '/%!/' => '<?= ',
        '/%([a-z])/' => '<? $1',
        "/(<\?=?) var\(([^\)]+)\)%/e"    =>   "cc_tpl_parse_var('$1 ','$2', ' ?>');",
        "/(<\?=?) var_check\(([^\)]+)\)%/e"    =>   "cc_tpl_parse_var_check('$1 ','$2', ' ?>');",
        "/<\? loop\(([^,]+),([^\)]+)\)%/e"  =>   "cc_tpl_parse_loop('$1','$2');",
        "/(<\?=?) call(?:_macro)?\(([^\)]+)\)%/e"    =>   "cc_tpl_parse_call_macro('$1 ','$2');",
        "/<\? if_(not_)?(?:empty|null)\(([^\)]+)\)%/e"  => "cc_tpl_parse_if_null('$1','$2');"  ,
        "/<\? define\(([^,]+),([^\)]+)\)%/e"=>   "cc_tpl_parse_define('$1','$2');",
        "/(<\?=?) chop\(([^,]+),([^\)]+)\)%/e"=>   "cc_tpl_parse_chop('$1', '$2','$3');",

                    "/<\? end_macro%/"                 =>   "<? } ?>",
                    "/<\? end_loop%/"                  =>   "<? } ?>",
                    "/<\? end_if%/"                    =>   "<? } ?>",

        "/<\? if\((.+)\)%/U"               =>   "<? if( $1 ) { ?>",
        "/<\? else%/"                      =>   "<? } else { ?>",
        "/<\? include_map\(([^\)]+)\)%/"   =>   "<? \$T->ImportMap('$1'); ?>",
        "/(<\?=?) url\(([^\)]+)\)%/"           =>   "$1 \$T->URL('$2') ?>",
        "/<\? add_stylesheet\(([^\)]+)\)%/"=>   "<? \$A['style_sheets'][] = '$1'; ?>",
        "/<\? import_map\(([^\)]+)\)%/"    =>   "<? \$T->ImportMap('$1'); ?>",
        "/<\? string_def\(([^,]+),(_\('.+'\))\)%/U" =>   "<? \$GLOBALS['str_$1'] = $2; ?>",
        "/(<\?=?) string\(([^\)]+)\)%/"        =>   "$1 \$GLOBALS['str_$2'] ?>",
        "/<\? return%/"                    =>   "<? return; ?>",
        "/<\? php\((.+)\)%/U"           =>   "<? $1 ?>",
        );

    $ttable["/<\? macro\(([^\)]+)\)%/"] = "<? function $bfunc$1(\$T,&\$A) { ?>";

    return preg_replace( array_keys($ttable), array_values($ttable), $text );
}

class cc_tpl_parser_test_cls
{
    function ImportMap() { }
}

if( !function_exists('_') ) { function _() { } }

function cc_tpl_parser_test()
{
    $T = new cc_tpl_parser_test_cls();
    $A = array();
    $A['page.php']['html_head'] = false;
    $hello = 'a';
    $world = 'b';
    $m = '';
    $text =<<<EOF

<div id="menu">

%loop(menu_groups,group)%
  <div class="menu_group">
    <p>%!var(#group/group_name)%</p>
    <ul>%loop(#group/menu_items,mi)%
      <li><a href="%!var(#mi/action)%" id="%!var_check(#mi/id)%">%var(#mi/menu_text)%</a></li>
    %end_loop% </ul>
  </div>
%end_loop%

%loop(custom_macros,macro)%
<div class="menu_group">
  %call_macro(\$macro)%
</div>
%end_loop%

</div> <!-- end of menu -->

%import_map(ccskins/default/skin-default-map.php)%

%define(html_head,page.php/html_head)%
%define(html_head,'page.php/html_head')%

%string_def(zip_title,_('Contents of ZIP Archive'))%

%php( if( strcmp(\$hello,\$world) && \$m % 5 ) { print('goofy'); } )%
%string_def(create_your_own,_('Create Your Own Remix Radio Station'))%;
EOF;
    
    $t = cc_tpl_parse_text($text,'');
    print $t;
    eval( '?>' . $t);
}

//cc_tpl_parser_test();

?>