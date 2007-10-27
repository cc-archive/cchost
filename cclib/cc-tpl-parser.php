<?

function _cc_tpl_flip_prefix($prefix)
{
    // typing an extra char '!' for the majority case was
    // stupid. Now '!' supress output (not sure what that's for lol)
    //
    return $prefix == '<?=' ? '<?' : '<?=';
}


function cc_tpl_parse_var($prefix,$var,$postfix)
{
    if( $prefix )
        $prefix = _cc_tpl_flip_prefix($prefix);
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
    $prefix = _cc_tpl_flip_prefix($prefix);
    $varname = cc_tpl_parse_var('',$var,'');

    return "$prefix empty($varname) ? '' : $varname $postfix";
}

function cc_tpl_parse_loop($arr, $item)
{
    $arr_name = cc_tpl_parse_var('',$arr,'');
    return "<? if( !empty($arr_name) ) { \$c_$item = count($arr_name); \$i_$item = 0; ".
           "foreach( $arr_name as \$k_$item => \$$item) { \$i_$item++; ?>";
}

function cc_tpl_parse_last($bang, $item)
{
    $item = preg_replace('/(#|\$)/','',$item);
    $bang = empty($bang) ? '' : '!';

    return "<? if( {$bang}(\$i_{$item} == \$c_{$item}) ) { ?>";
}

function cc_tpl_parse_call_macro($prefix, $mac)
{
    $prefix = _cc_tpl_flip_prefix($prefix);
    $mac = cc_tpl_parse_var('',$mac,'');

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

    return "<? $left = $right; \n?>";
}

function cc_tpl_parse_file($filename,$bfunc)
{
    //print "parsing: $filename\n<br />";
    return cc_tpl_parse_text(file_get_contents($filename),$bfunc);
}

function cc_tpl_parse_chop($prefix,$varname,$amt)
{
    $prefix = _cc_tpl_flip_prefix($prefix);
    $var = cc_tpl_parse_var('',$varname,'');
    return "$prefix CC_strchop($var,$amt,true); ?>";
}

function cc_tpl_parse_date($prefix,$varname,$fmt)
{
    $prefix = _cc_tpl_flip_prefix($prefix);
    $var = cc_tpl_parse_var('',$varname,'');
    return "$prefix CC_datefmt($var,'$fmt'); ?>";
}

function cc_tpl_parse_inspect($varname)
{
    $var = cc_tpl_parse_var('',$varname,'');
    return "<? CCDebug::Enable(true); CCDebug::PrintVar($var,false); ?>";
}

function cc_tpl_parse_url($prefix,$varname)
{
    $prefix = _cc_tpl_flip_prefix($prefix);
    if( $varname{0} == '#' )
        $v = '$' . substr($varname,1);
    elseif( $varname{0} == "'" )
        $v = $varname;
    else
        $v = "'$varname'";

    return "$prefix \$T->URL($v); ?>";
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
        
        '/((?:\s|^)+%%[^%]+%%)/' => '',

        '/^\s+/' => '',
        '/\s+$/' => '',
        '/%\s+%/' => '%%',

        '/%!/'                 => '<?= ',
        '/%([a-z\(])/'           => '<? $1',

        "/(<\?=?) (?:var)?{$op}{$a}{$cp}%/e"                   =>   "cc_tpl_parse_var('$1 ','$2', ' ?>');",
        "/(<\?=?) var_check{$op}{$a}{$cp}%/e"             =>   "cc_tpl_parse_var_check('$1 ','$2', ' ?>');",
        "/<\? loop{$op}{$ac}{$a}{$cp}%/e"                 =>   "cc_tpl_parse_loop('$1','$2');",
        "/(<\?=?) call(?:_macro)?{$op}{$a}{$cp}%/e"       =>   "cc_tpl_parse_call_macro('$1 ','$2');",
        "/<\? if_(not_)?(?:empty|null){$op}{$a}{$cp}%/e"  =>   "cc_tpl_parse_if_null('$1','$2');"  ,
        "/<\? (?:define|map){$op}{$ac}{$a}{$cp}%/e"       =>   "cc_tpl_parse_define('$1','$2');",
        "/(<\?=?) chop{$op}{$ac}{$a}{$cp}%/e"             =>   "cc_tpl_parse_chop('$1', '$2','$3');",
        "/(<\?=?) date{$op}{$ac}{$qa}{$cp}%/e"            =>   "cc_tpl_parse_date('$1', '$2','$3');",
        "/<\? inspect{$op}{$a}{$cp}%/e"                   =>   "cc_tpl_parse_inspect('$1');",
        "/<\? if_(not_)last{$op}{$a}{$cp}%/e"             =>   "cc_tpl_parse_last('$1','$2');",  
        "/(<\?=?) url{$op}{$a}{$cp}%/e"                   =>   "cc_tpl_parse_url('$1','$2');",

        "/<\? else %/"              =>   "<? } else { ?>",
        "/<\? end_(?:macro|if)%/"   =>   "<? } ?>",
        "/<\? end_loop%/"           =>   "<? } } ?>",

        "/<\? if\((.+)\)%/U"                              =>   "<? if( $1 ) { ?>",
        "/<\? else%/"                                     =>   "<? } else { ?>",
        "/<\? add_stylesheet{$op}{$aoq}\)%/"              =>   "<? \$A['style_sheets'][] = '$1'; ?>",
        "/<\? append{$op}{$ac}{$aoq}{$cp}%/"              =>   "<? \$A['$1'][] = '$2'; ?>",
        "/<\? import_skin{$op}{$aoq}{$cp}%/"              =>   "<? \$T->ImportSkin('$1'); ?>",
        "/<\? title{$op}{$a}{$cp}%/"                      =>   "<? \$A['page-title'] = \$GLOBALS['str_$1']; \$T->Call('print_page_title'); ?>",
        "/(<\?=?) key{$op}{$a}{$cp}%/"                    =>   "$1 \$k_$1 ?>",
        "/<\? string_def{$op}{$a},(_\('.+'\)){$cp}%/U"    =>   "<? \$GLOBALS['str_$1'] = $2; ?>",
        "/(<\?=?) string{$op}{$a}{$cp}%/"                 =>   "$1 \$GLOBALS['str_$2'] ?>",
        "/<\? return%/"                                   =>   "<? return; ?>",
        "/<\? inherit{$op}{$ac}{$aoq}{$cp}%/"             =>   "<? \$T->Inherit('$1','$2'); ?>",
        "/<\? call_parent%/"                              =>   "<? \$T->CallParent(); ?>",
        "/<\? settings{$op}{$ac}{$a}{$cp}%/"              =>   "<? \$A['$2'] = CC_get_config('$1'); ?>",
        "/<\? un(?:define|map){$op}{$a}{$cp}%/"           =>   "<? unset(\$A['$1']); ?>",
        );

    $ttable["/<\? macro\(([^\)]+)\)%/"] = "<? function $bfunc$1(\$T,&\$A) { ?>";

    $text = preg_replace( array_keys($ttable), array_values($ttable), $text );

    return preg_replace( '/\?><\?=?/', '', $text );  
}


?>