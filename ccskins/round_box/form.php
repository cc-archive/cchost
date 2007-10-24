<?

function _t_form_html_form($T,&$A) 
{
    $F = $A['curr_form'];

    if ( !empty($F['form_id']))
        print "<script >form_id = '{$F['form_id']}';</script>\n";

    $onsubmit = empty($F['hide_on_submit']) ? '' : 'onsubmit="return the_formMask.dull_screen();" ';
    ?><form  action="<?= $F['form_action']?>" method="<?= $F['form_method']?>" class="cc_form" name="<?= $F['form_id']?>" id="<?= $F['form_id']?>" enctype="<?= empty($F['form-data']) ? null : $F['form-data']; ?>" <?= $onsubmit ?> >
    <?

    if ( !empty($F['form_macros']))
        foreach( $F['form_macros'] as $macro )
           $T->Call($macro);

    if ( !empty($F['html_form_grid_columns'])) 
        $T->Call('form.xml/grid_form_fields');

    if ( !empty($F['html_form_fields']))
        $T->Call('form.xml/form_fields');

    if ( !empty($F['submit_text'])) {
        if( !empty($GLOBALS[$F['submit_text']]) )
            $F['submit_text'] = $GLOBALS[$F['submit_text']];
        ?><input  type="submit" name="form_submit" id="form_submit" class="cc_form_submit" value="<?= $F['submit_text'] ?>"></input><?
    }

    if ( !empty($F['html_hidden_fields'])) 
    {
        foreach( $F['html_hidden_fields'] as $H )
            print "\n<input  type=\"hidden\" name=\"{$H['hidden_name']}\" id=\"{$H['hidden_name']}\" value=\"{$H['hidden_value']}\" />";
    }

    print "</form>\n";

    if ( !empty($F['html_meta_row'])) {

    ?>   <script >
            var add_row_num = 1;
            function add_the_row()
            {
                var tableobj = $('table_<?= $F['form_id']?>');
                var row_num = <?= $F['html_form_grid_num_rows']?> + add_row_num;
                ++add_row_num;
                var row = tableobj.insertRow(row_num);
                var num_cols = <?= $F['html_form_grid_num_cols']?>;
                var td;
                <?  $ci = 0;
                    foreach( $F['html_meta_row'] as $HMR ) 
                    { ?>
                td = row.insertCell(<?= $ci?>);
                td.innerHTML = '<?= $HMR?>'.replace(/%i%/g,"" + row_num); 
                <?  $ci++; } ?>
            }
          </script>
    <?
    } // END: if

    if ( isset($F['html_add_row_caption']) ) {
        ?><button  onclick="add_the_row(); return false;"><?= $F['html_add_row_caption']?></button><?
    }

} // END: function html_form


//------------------------------------- 
function _t_form_form_fields($T,&$A) {
   
    print "<table  class=\"cc_form_table\">\n";

    foreach( $A['curr_form']['html_form_fields'] as $F )
    { 
        _t_form_string_helper($F,'form_error');

        if ( !empty($F['form_error']))
            print "<tr  class=\"cc_form_error_row\"><td ></td><td  class=\"cc_form_error\">{$F['form_error']}</td></tr>\n";

        print '<tr class="cc_form_row">';

        _t_form_string_helper($F,'form_tip');

        $tip = empty($F['form_tip']) ? '' : '<span>' . $F['form_tip'] . '</span>';

        _t_form_string_helper($F,'label');

        if ( !empty($F['label'])) {
            print "<td  class=\"cc_form_label\"><div>{$F['label']}</div>$tip</td>";
            $span = '';
        } else {
            $span = 'colspan="2"';
        }

        print "<td $span class=\"cc_form_element\">";

        if ( !empty($F['macro']))
        {
            $A['field'] = $F;
            $T->Call($F['macro']);
        }

        ?><?= $F['form_element']?></td><?

        print "</tr>\n";
    } // END: for loop

    print "</table>\n";

} // END: function form_fields


function _t_form_string_helper(&$F,$field)
{
    $strn = $field . '_str';

    if( !empty($F[$strn]) )
    {
        if( !empty($F[$field . '_args']) )
        {
            $fmt = $GLOBALS[$F[$strn]];
            $str = "\$s = sprintf('{$fmt}'";
            foreach( $F['form_tip_args'] as $arg )
                $str .= ",'" . str_replace("'", "\\'", $arg) . "'";
            $str .= ");";
            //CCDebug::PrintVar($str);
            eval($str);
            $F[$field] = $s;
        }
        else
        {
            $F[$field] = $GLOBALS[$F[$strn]];
        }
    }
}

//------------------------------------- 
function _t_form_grid_form_fields($T,&$A) {
   

?><table  class="cc_grid_form_table" id="table_<?= $A['curr_form']['form_id']?>">
<tr  class="cc_grid_form_header_row">
<?

$carr106 = $A['curr_form']['html_form_grid_columns'];
$cc106= count( $carr106);
$ck106= array_keys( $carr106);
for( $ci106= 0; $ci106< $cc106; ++$ci106)
{ 
   $A['curr_form']['head'] = $carr106[ $ck106[ $ci106 ] ];
   
?><th  class="cc_grid_form_header"><?= $A['curr_form']['head']['column_name']?></th><?
} // END: for loop

?></tr>
<?

$carr107 = $A['curr_form']['html_form_grid_rows'];
$cc107= count( $carr107);
$ck107= array_keys( $carr107);
for( $ci107= 0; $ci107< $cc107; ++$ci107)
{ 
   $A['curr_form']['row'] = $carr107[ $ck107[ $ci107 ] ];
   
if ( !empty($A['curr_form']['row']['form_error'])) {

?><tr  class="cc_form_error_row"><td ></td>
<td  colspan="<?= $A['curr_form']['repeat']['row']['length']?>" class="cc_form_error"><?= $A['curr_form']['row']['form_error']?></td>
</tr><?
} // END: if

?><tr  class="cc_form_row">
<?

$carr108 = $A['curr_form']['row']['html_form_grid_fields'];
$cc108= count( $carr108);
$ck108= array_keys( $carr108);
for( $ci108= 0; $ci108< $cc108; ++$ci108)
{ 
   $F = $carr108[ $ck108[ $ci108 ] ];
   
?><td ><?= $F['form_grid_element']?>
           </td><?
} // END: for loop

?></tr>
<?
} // END: for loop

?></table>
<?
} // END: function grid_form_fields


//------------------------------------- 
function _t_form_submit_forms($T,&$A) 
{
   ?><div  class="cc_submit_forms_outer"><?

    foreach($A['submit_form_infos'] as $SI )
    {
        ?><div  class="cc_submit_forms cc_round_box_bw"><?

        if ( !empty($SI['logo'])) 
            ?><img  src="<?= $SI['logo']?>" /><?

        ?><h2 ><?= $SI['text']?></h2>
        <div  class="cc_submit_form_help"><?= $SI['help']?></div>
        <div  class="cc_submit_form_url"><?
            if ( !($SI['quota_reached']) )
                { ?><a  href="<?= $SI['action']?>"><?= $SI['text']?></a><? }
            else
                { ?><span  class="cc_quota_message"><?= $SI['quota_message']?></span><? }

        ?></div>
        </div><?
    } 

    ?></div><?
} // END: function submit_forms


//------------------------------------- 
function _t_form_show_form_about($T,&$A) 
{
    print '<div id="cc_form_help_container"><div class="cc_round_box">';
    foreach( $A['curr_form']['form_about'] as $FA )   
        print "<div  class=\"cc_form_about\">{$FA}</div>\n";
    print '</div></div>';

} // END: function show_form_about

?>