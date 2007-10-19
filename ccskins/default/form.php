<?



//------------------------------------- 
function _t_form_grow_textarea_script($T,&$_TV) {
   

?><script  type="text/javascript" src="<?= $_TV['root-url']?>js/upload_form.js"></script>
<?
} // END: function grow_textarea_script


//------------------------------------- 
function _t_form_hide_form_on_submit_script($T,&$_TV) {
   

?><script  type="text/javascript" src="<?= $_TV['root-url']?>js/upload_form.js"></script>
<?
} // END: function hide_form_on_submit_script


//------------------------------------- 
function _t_form_html_form($T,&$_TV) {
   

if ( isset($_TV['form_hide_msg']) ) {

?><div  id="cc_form_submit_message" style="display:none;">
<h2 ><?= $_TV['form_hide_msg']['title']?></h2>
<?

$carr101 = $_TV['form_hide_msg']['paras'];
$cc101= count( $carr101);
$ck101= array_keys( $carr101);
for( $ci101= 0; $ci101< $cc101; ++$ci101)
{ 
   $_TV['msg'] = $carr101[ $ck101[ $ci101 ] ];
   
?><p ><?= $_TV['msg']?></p>
<?
} // END: for loop

?></div><?
} // END: if

if ( !empty($_TV['form_id'])) {

?><script >
          form_id = '<?= $_TV['form_id']?>';
        </script>
<?
} // END: if

?><form  action="<?= $_TV['form_action']?>" method="<?= $_TV['form_method']?>" class="cc_form" name="<?= $_TV['form_id']?>" id="<?= $_TV['form_id']?>" enctype="<?= empty($_TV['form-data']) ? null : $_TV['form-data']; ?>">
<?

if ( !empty($_TV['form_macros'])) {

$carr102 = $_TV['form_macros'];
$cc102= count( $carr102);
$ck102= array_keys( $carr102);
for( $ci102= 0; $ci102< $cc102; ++$ci102)
{ 
   $_TV['macro_name'] = $carr102[ $ck102[ $ci102 ] ];
   $_TV['tname'] = $_TV['macro_name'];
$T->Call($_TV['tname']);
} // END: for loop
} // END: if

if ( !empty($_TV['html_form_grid_columns'])) {
$T->Call('form.xml/grid_form_fields');
} // END: if

if ( !empty($_TV['html_form_fields'])) {
$T->Call('form.xml/form_fields');
} // END: if

if ( !empty($_TV['submit_text'])) {

?><input  type="submit" name="form_submit" id="form_submit" class="cc_form_submit" value="<?= $_TV['submit_text'] ?>"></input>
<?
} // END: if

if ( !empty($_TV['html_hidden_fields'])) {

$carr103 = $_TV['html_hidden_fields'];
$cc103= count( $carr103);
$ck103= array_keys( $carr103);
for( $ci103= 0; $ci103< $cc103; ++$ci103)
{ 
   $_TV['hfield'] = $carr103[ $ck103[ $ci103 ] ];
   
?><input  type="hidden" name="<?= $_TV['hfield']['hidden_name']?>" id="<?= $_TV['hfield']['hidden_name']?>" value="<?= $_TV['hfield']['hidden_value']?>"></input><?
} // END: for loop
} // END: if

?></form>
<?

if ( !empty($_TV['html_meta_row'])) {

?><script >
        var add_row_num = 1;
        function add_the_row()
        {
            var tableobj = new cc_obj('table_<?= $_TV['form_id']?>');
            var row_num = <?= $_TV['html_form_grid_num_rows']?> + add_row_num;
            ++add_row_num;
            var row = tableobj.obj.insertRow(row_num);
            var num_cols = <?= $_TV['html_form_grid_num_cols']?>;
            var td;<?

$carr104 = $_TV['html_meta_row'];
$cc104= count( $carr104);
$ck104= array_keys( $carr104);
for( $ci104= 0; $ci104< $cc104; ++$ci104)
{ 
   $_TV['mrow'] = $carr104[ $ck104[ $ci104 ] ];
   
?>
            td = row.insertCell(<?= $ci104?>);
            td.innerHTML = '<?= $_TV['mrow']?>'.replace(/%i%/g,"" + row_num); <?
} // END: for loop

?>
        }
      </script>
<?
} // END: if

if ( isset($_TV['html_add_row_caption']) ) {

?><button  onclick="add_the_row(); return false;"><?= $_TV['html_add_row_caption']?></button><?
} // END: if
} // END: function html_form


//------------------------------------- 
function _t_form_form_fields($T,&$_TV) {
   

    print "<table  class=\"cc_form_table\">\n";

    $carr105 = $_TV['html_form_fields'];
    $cc105= count( $carr105);
    $ck105= array_keys( $carr105);
    for( $ci105= 0; $ci105< $cc105; ++$ci105)
    { 
       $_TV['field'] = $carr105[ $ck105[ $ci105 ] ];
       
        if ( !empty($_TV['field']['form_error'])) {
            print "<tr  class=\"cc_form_error_row\"><td ></td><td  class=\"cc_form_error\">{$_TV['field']['form_error']}</td><td></td></tr>\n";
        } // END: if

        print '<tr  class="cc_form_row">';

        if ( !empty($_TV['field']['label'])) {
            print "<td  class=\"cc_form_label\">{$_TV['field']['label']}</td>";
            $span = '';
        } else {
            $span = 'colspan="2"';
        }

        print "<td $span class=\"cc_form_element\">";

        if ( !empty($_TV['field']['macro'])) {
            $_TV['tname'] = $_TV['field']['macro'];
            $T->Call($_TV['tname']);
        } // END: if

        ?><?= $_TV['field']['form_element']?></td><?

        if ( !empty($_TV['field']['form_tip'])) {
            ?><td class="cc_form_tip"><?= $_TV['field']['form_tip']?></td><?
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
   

?><table  class="cc_grid_form_table" id="table_<?= $_TV['form_id']?>">
<tr  class="cc_grid_form_header_row">
<?

$carr106 = $_TV['html_form_grid_columns'];
$cc106= count( $carr106);
$ck106= array_keys( $carr106);
for( $ci106= 0; $ci106< $cc106; ++$ci106)
{ 
   $_TV['head'] = $carr106[ $ck106[ $ci106 ] ];
   
?><th  class="cc_grid_form_header"><?= $_TV['head']['column_name']?></th><?
} // END: for loop

?></tr>
<?

$carr107 = $_TV['html_form_grid_rows'];
$cc107= count( $carr107);
$ck107= array_keys( $carr107);
for( $ci107= 0; $ci107< $cc107; ++$ci107)
{ 
   $_TV['row'] = $carr107[ $ck107[ $ci107 ] ];
   
if ( !empty($_TV['row']['form_error'])) {

?><tr  class="cc_form_error_row"><td ></td>
<td  colspan="<?= $_TV['repeat']['row']['length']?>" class="cc_form_error"><?= $_TV['row']['form_error']?></td>
</tr><?
} // END: if

?><tr  class="cc_form_row">
<?

$carr108 = $_TV['row']['html_form_grid_fields'];
$cc108= count( $carr108);
$ck108= array_keys( $carr108);
for( $ci108= 0; $ci108< $cc108; ++$ci108)
{ 
   $_TV['field'] = $carr108[ $ck108[ $ci108 ] ];
   
?><td ><?= $_TV['field']['form_grid_element']?>
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

$carr109 = $_TV['submit_form_infos'];
$cc109= count( $carr109);
$ck109= array_keys( $carr109);
for( $ci109= 0; $ci109< $cc109; ++$ci109)
{ 
   $_TV['submit_info'] = $carr109[ $ck109[ $ci109 ] ];
   
?><div  class="cc_submit_forms">
<?

if ( !empty($_TV['submit_info']['logo'])) {

?><img  src="<?= $_TV['submit_info']['logo']?>" /><?
} // END: if

?><h2 ><?= $_TV['submit_info']['text']?></h2>
<div  class="cc_submit_form_help"><?= $_TV['submit_info']['help']?></div>
<div  class="cc_submit_form_url">
<?

if ( !($_TV['submit_info']['quota_reached']) ) {

?><a  href="<?= $_TV['submit_info']['action']?>"><?= $_TV['submit_info']['text']?></a><?
} // END: if

if ( !empty($_TV['submit_info']['quota_reached'])) {

?><span  class="cc_quota_message"><?= $_TV['submit_info']['quota_message']?></span><?
} // END: if

?></div>
</div><?
} // END: for loop

?></div>
<?
} // END: function submit_forms


//------------------------------------- 
function _t_form_show_form_about($T,&$_TV) {
   

$carr110 = $_TV['form_about'];
$cc110= count( $carr110);
$ck110= array_keys( $carr110);
for( $ci110= 0; $ci110< $cc110; ++$ci110)
{ 
   $_TV['htext'] = $carr110[ $ck110[ $ci110 ] ];
   
?><div  class="cc_form_about">
        <?= $_TV['htext']?>
    </div><?
} // END: for loop
} // END: function show_form_about

?>