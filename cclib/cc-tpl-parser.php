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

function cc_tpl_parse_date($prefix,$varname,$fmt)
{
    $var = cc_tpl_parse_var('',$varname,'');
    return "$prefix CC_datefmt($var,'$fmt'); ?>";
}

function cc_tpl_parse_inspect($varname)
{
    $var = cc_tpl_parse_var('',$varname,'');
    return "<? CCDebug::Enable(true); CCDebug::PrintVar($var,false); ?>";
}

function cc_tpl_parse_text($text,$bfunc)
{
    static $ttable;

    $w = '(?:\s+)?';        // optional whitespace
    $op = '\(' . $w;        // open paren
    $cp = $w . '\)';        // close paren
    $c = $w . ',' . $w;     // comma
    $ac = '([^,]+)' . $c;   // arg followed by comma
    $a = '([^\)]+)';        // final arg
    $qa = "'([^']+)'";      // quoted arg
    $aoq = "'?([^\)']+)'?"; // arg, optional quotes

    if( !isset($ttable) )
      $ttable = array(
        
        '/((?:\s)+%%[^%]+%%)/' => '',

        '/%!/'                 => '<?= ',
        '/%([a-z])/'           => '<? $1',

        "/(<\?=?) var{$op}{$a}{$cp}%/e"                   =>   "cc_tpl_parse_var('$1 ','$2', ' ?>');",
        "/(<\?=?) var_check{$op}{$a}{$cp}%/e"             =>   "cc_tpl_parse_var_check('$1 ','$2', ' ?>');",
        "/<\? loop{$op}{$ac}{$a}{$cp}%/e"                 =>   "cc_tpl_parse_loop('$1','$2');",
        "/(<\?=?) call(?:_macro)?{$op}{$a}{$cp}%/e"       =>   "cc_tpl_parse_call_macro('$1 ','$2');",
        "/<\? if_(not_)?(?:empty|null){$op}{$a}{$cp}%/e"  =>   "cc_tpl_parse_if_null('$1','$2');"  ,
        "/<\? (?:define|map){$op}{$ac}{$a}{$cp}%/e"       =>   "cc_tpl_parse_define('$1','$2');",
        "/(<\?=?) chop{$op}{$ac}{$a}{$cp}%/e"             =>   "cc_tpl_parse_chop('$1', '$2','$3');",
        "/(<\?=?) date{$op}{$ac}{$qa}{$cp}%/e"            =>   "cc_tpl_parse_date('$1', '$2','$3');",
        "/<\? inspect{$op}{$a}{$cp}%/e"                   =>   "cc_tpl_parse_inspect('$1');",

        "/<\? end_(?:macro|loop|if)%/"   =>   "<? } ?>",

        "/<\? if\((.+)\)%/U"                              =>   "<? if( $1 ) { ?>",
        "/<\? else%/"                                     =>   "<? } else { ?>",
        "/(<\?=?) url{$op}{$a}{$cp}%/"                    =>   "$1 \$T->URL('$2') ?>",
        "/<\? add_stylesheet{$op}{$aoq}\)%/"              =>   "<? \$A['style_sheets'][] = '$1'; ?>",
        "/<\? append{$op}{$ac}{$aoq}{$cp}%/"              =>   "<? \$A['$1'][] = '$2'; ?>",
        "/<\? import_skin{$op}{$aoq}{$cp}%/"               =>   "<? \$T->ImportSkin('$1'); ?>",
        "/<\? title{$op}{$a}{$cp}%/"                      =>   "<? \$A['page-title'] = \$GLOBALS['str_$1']; \$T->Call('print_page_title'); ?>",
        "/<\? string_def{$op}{$a},(_\('.+'\)){$cp}%/U"    =>   "<? \$GLOBALS['str_$1'] = $2; ?>",
        "/(<\?=?) string{$op}{$a}{$cp}%/"                 =>   "$1 \$GLOBALS['str_$2'] ?>",
        "/<\? return%/"                                   =>   "<? return; ?>",
        "/<\? php{$op}(.+){$cp}%/U"                       =>   "<? $1 ?>",
        "/<\? inherit{$op}{$ac}{$aoq}{$cp}%/"             =>   "<? \$T->Inherit('$1','$2'); ?>",
        "/<\? call_parent%/"                              =>   "<? \$T->CallParent(); ?>",
        );

    $ttable["/<\? macro\(([^\)]+)\)%/"] = "<? function $bfunc$1(\$T,&\$A) { ?>";

    return preg_replace( array_keys($ttable), array_values($ttable), $text );
}

class cc_tpl_parser_test_cls
{
    function ImportSkin() { }
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

%import_skin(ccskins/foo)%

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