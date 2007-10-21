<?



//------------------------------------- 
function _t_form_grow_textarea_script($T,&$_TV) {
} // END: function grow_textarea_script


//------------------------------------- 
function _t_form_hide_form_on_submit_script($T,&$_TV) {
} // END: function hide_form_on_submit_script


//------------------------------------- 
function _t_form_html_form($T,&$_TV) {
   

if ( isset($_TV['curr_form']['form_hide_msg']) ) {

?><div  id="cc_form_submit_message" style="display:none;">
<h2 ><?= $_TV['curr_form']['form_hide_msg']['title']?></h2>
<?

$carr101 = $_TV['curr_form']['form_hide_msg']['paras'];
$cc101= count( $carr101);
$ck101= array_keys( $carr101);
for( $ci101= 0; $ci101< $cc101; ++$ci101)
{ 
   $_TV['curr_form']['msg'] = $carr101[ $ck101[ $ci101 ] ];
   
?><p ><?= $_TV['curr_form']['msg']?></p>
<?
} // END: for loop

?></div><?
} // END: if

if ( !empty($_TV['curr_form']['form_id'])) {

?><script >
          form_id = '<?= $_TV['curr_form']['form_id']?>';
        </script>
<?
} // END: if

?><form  action="<?= $_TV['curr_form']['form_action']?>" method="<?= $_TV['curr_form']['form_method']?>" class="cc_form" name="<?= $_TV['curr_form']['form_id']?>" id="<?= $_TV['curr_form']['form_id']?>" enctype="<?= empty($_TV['curr_form']['form-data']) ? null : $_TV['curr_form']['form-data']; ?>">
<?

if ( !empty($_TV['curr_form']['form_macros'])) {
    foreach( $_TV['curr_form']['form_macros'] as $macro )
       $T->Call($macro);
} // END: if

if ( !empty($_TV['curr_form']['html_form_grid_columns'])) {
    $T->Call('form.xml/grid_form_fields');
} // END: if

if ( !empty($_TV['curr_form']['html_form_fields'])) {
    $T->Call('form.xml/form_fields');
} // END: if

if ( !empty($_TV['curr_form']['submit_text'])) {

?><input  type="submit" name="form_submit" id="form_submit" class="cc_form_submit" value="<?= $_TV['curr_form']['submit_text'] ?>"></input>
<?
} // END: if

if ( !empty($_TV['curr_form']['html_hidden_fields'])) {

$carr103 = $_TV['curr_form']['html_hidden_fields'];
$cc103= count( $carr103);
$ck103= array_keys( $carr103);
for( $ci103= 0; $ci103< $cc103; ++$ci103)
{ 
   $_TV['curr_form']['hfield'] = $carr103[ $ck103[ $ci103 ] ];
   
?><input  type="hidden" name="<?= $_TV['curr_form']['hfield']['hidden_name']?>" id="<?= $_TV['curr_form']['hfield']['hidden_name']?>" value="<?= $_TV['curr_form']['hfield']['hidden_value']?>"></input><?
} // END: for loop
} // END: if

?></form>
<?

if ( !empty($_TV['curr_form']['html_meta_row'])) {

?><script >
        var add_row_num = 1;
        function add_the_row()
        {
            var tableobj = new cc_obj('table_<?= $_TV['curr_form']['form_id']?>');
            var row_num = <?= $_TV['curr_form']['html_form_grid_num_rows']?> + add_row_num;
            ++add_row_num;
            var row = tableobj.obj.insertRow(row_num);
            var num_cols = <?= $_TV['curr_form']['html_form_grid_num_cols']?>;
            var td;<?

$carr104 = $_TV['curr_form']['html_meta_row'];
$cc104= count( $carr104);
$ck104= array_keys( $carr104);
for( $ci104= 0; $ci104< $cc104; ++$ci104)
{ 
   $_TV['curr_form']['mrow'] = $carr104[ $ck104[ $ci104 ] ];
   
?>
            td = row.insertCell(<?= $ci104?>);
            td.innerHTML = '<?= $_TV['curr_form']['mrow']?>'.replace(/%i%/g,"" + row_num); <?
} // END: for loop

?>
        }
      </script>
<?
} // END: if

if ( isset($_TV['curr_form']['html_add_row_caption']) ) {

?><button  onclick="add_the_row(); return false;"><?= $_TV['curr_form']['html_add_row_caption']?></button><?
} // END: if
} // END: function html_form


//------------------------------------- 
function _t_form_form_fields($T,&$_TV) {
   

    print "<table  class=\"cc_form_table\">\n";

    $carr105 = $_TV['curr_form']['html_form_fields'];
    $cc105= count( $carr105);
    $ck105= array_keys( $carr105);
    for( $ci105= 0; $ci105< $cc105; ++$ci105)
    { 
       $_TV['curr_form']['field'] = $carr105[ $ck105[ $ci105 ] ];
       
        if ( !empty($_TV['curr_form']['field']['form_error'])) {
            print "<tr  class=\"cc_form_error_row\"><td ></td><td  class=\"cc_form_error\">{$_TV['curr_form']['field']['form_error']}</td><td></td></tr>\n";
        } // END: if

        print '<tr  class="cc_form_row">';

        if ( !empty($_TV['curr_form']['field']['label'])) {
            print "<td  class=\"cc_form_label\">{$_TV['curr_form']['field']['label']}</td>";
            $span = '';
        } else {
            $span = 'colspan="2"';
        }

        print "<td $span class=\"cc_form_element\">";

        if ( !empty($_TV['curr_form']['field']['macro'])) {
            $_TV['curr_form']['tname'] = $_TV['curr_form']['field']['macro'];
            $T->Call($_TV['curr_form']['tname']);
        } // END: if

        ?><?= $_TV['curr_form']['field']['form_element']?></td><?

        if ( !empty($_TV['curr_form']['field']['form_tip'])) {
            ?><td class="cc_form_tip"><?= $_TV['curr_form']['field']['form_tip']?></td><?
        }
        else {
            print "<td></td>";
        }

        print "</tr>\n";
    } // END: for loop

    print "</table>\n";

} // END: function form_fields


//------------------------------------- 
function _t_form_grid_form_fields($T,&$_TV) {
   

?><table  class="cc_grid_form_table" id="table_<?= $_TV['curr_form']['form_id']?>">
<tr  class="cc_grid_form_header_row">
<?

$carr106 = $_TV['curr_form']['html_form_grid_columns'];
$cc106= count( $carr106);
$ck106= array_keys( $carr106);
for( $ci106= 0; $ci106< $cc106; ++$ci106)
{ 
   $_TV['curr_form']['head'] = $carr106[ $ck106[ $ci106 ] ];
   
?><th  class="cc_grid_form_header"><?= $_TV['curr_form']['head']['column_name']?></th><?
} // END: for loop

?></tr>
<?

$carr107 = $_TV['curr_form']['html_form_grid_rows'];
$cc107= count( $carr107);
$ck107= array_keys( $carr107);
for( $ci107= 0; $ci107< $cc107; ++$ci107)
{ 
   $_TV['curr_form']['row'] = $carr107[ $ck107[ $ci107 ] ];
   
if ( !empty($_TV['curr_form']['row']['form_error'])) {

?><tr  class="cc_form_error_row"><td ></td>
<td  colspan="<?= $_TV['curr_form']['repeat']['row']['length']?>" class="cc_form_error"><?= $_TV['curr_form']['row']['form_error']?></td>
</tr><?
} // END: if

?><tr  class="cc_form_row">
<?

$carr108 = $_TV['curr_form']['row']['html_form_grid_fields'];
$cc108= count( $carr108);
$ck108= array_keys( $carr108);
for( $ci108= 0; $ci108< $cc108; ++$ci108)
{ 
   $_TV['curr_form']['field'] = $carr108[ $ck108[ $ci108 ] ];
   
?><td ><?= $_TV['curr_form']['field']['form_grid_element']?>
           </td><?
} // END: for loop

?></tr>
<?
} // END: for loop

?></table>
<?
} // END: function grid_form_fields


//------------------------------------- 
function _t_form_submit_forms($T,&$_TV) {
   

?><div  class="cc_submit_forms_outer">
<?

$carr109 = $_TV['curr_form']['submit_form_infos'];
$cc109= count( $carr109);
$ck109= array_keys( $carr109);
for( $ci109= 0; $ci109< $cc109; ++$ci109)
{ 
   $_TV['curr_form']['submit_info'] = $carr109[ $ck109[ $ci109 ] ];
   
?><div  class="cc_submit_forms">
<?

if ( !empty($_TV['curr_form']['submit_info']['logo'])) {

?><img  src="<?= $_TV['curr_form']['submit_info']['logo']?>" /><?
} // END: if

?><h2 ><?= $_TV['curr_form']['submit_info']['text']?></h2>
<div  class="cc_submit_form_help"><?= $_TV['curr_form']['submit_info']['help']?></div>
<div  class="cc_submit_form_url">
<?

if ( !($_TV['curr_form']['submit_info']['quota_reached']) ) {

?><a  href="<?= $_TV['curr_form']['submit_info']['action']?>"><?= $_TV['curr_form']['submit_info']['text']?></a><?
} // END: if

if ( !empty($_TV['curr_form']['submit_info']['quota_reached'])) {

?><span  class="cc_quota_message"><?= $_TV['curr_form']['submit_info']['quota_message']?></span><?
} // END: if

?></div>
</div><?
} // END: for loop

?></div>
<?
} // END: function submit_forms


//------------------------------------- 
function _t_form_show_form_about($T,&$_TV) {
   

$carr110 = $_TV['curr_form']['form_about'];
$cc110= count( $carr110);
$ck110= array_keys( $carr110);
for( $ci110= 0; $ci110< $cc110; ++$ci110)
{ 
   $_TV['curr_form']['htext'] = $carr110[ $ck110[ $ci110 ] ];
   
?><div  class="cc_form_about">
        <?= $_TV['curr_form']['htext']?>
    </div><?
} // END: for loop
} // END: function show_form_about

?>