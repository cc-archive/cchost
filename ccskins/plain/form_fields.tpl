%macro(form_fields)%
<table class="cc_form_table">
    %loop(curr_form/html_form_fields,F)%
        %if_not_empty(#F/form_error)%
            <tr class="cc_form_error_row"><td></td><td class="cc_form_error">%string(#F/form_error)%</td></tr>
        %end_if%
        <tr class="cc_form_row">
        <td  class="cc_form_label"><div>%text(#F/label)%</div><span>%text(#F/form_tip)%</span></td>
        <td class="cc_form_element">%if_not_null(#F/macro)% %call(#F/macro)% %end_if%<!-- -->%(#F/form_element)%</td></tr>
    %end_loop%
</table>
%end_macro%

%macro(stacked_form_fields)%
<table class="cc_form_table">
    %loop(curr_form/html_form_fields,F)%
        %if_not_empty(#F/form_error)%
            <tr class="cc_form_error_row"><td></td><td class="cc_form_error">%string(#F/form_error)%</td></tr>
        %end_if%
        <tr class="cc_form_row">
        <td  class="cc_form_label"><div>%text(#F/label)%</div><span>%text(#F/form_tip)%</span></td>
        </tr>
        <tr><td class="cc_form_element">%if_not_null(#F/macro)% %call(#F/macro)% %end_if%<!-- -->%(#F/form_element)%</td></tr></tr>
    %end_loop%
</table>
%end_macro%

%macro(flat_grid_form_fields)%
<table class="cc_grid_form_table" id="table_%(curr_form/form_id)%">
<tr class="cc_grid_form_header_row">
%loop(curr_form/html_form_grid_columns,C)%
  <th class="cc_grid_form_header">%text(#C/column_name)%</th>
%end_loop%
</tr>
%loop(curr_form/html_form_grid_rows,R)%
   %if_not_null(#R/form_error)%
      <tr class="cc_form_error_row"><td></td><td colspan="%(#c_R)%" class="cc_form_error">%text(#R/form_error)%</td></tr>
   %end_if%
   <tr class="cc_form_row">
   %loop(#R/html_form_grid_fields,F)%
     <td>%(#F/form_grid_element)%</td>
   %end_loop%
   </tr>
%end_loop%
</table>
%end_macro%

%macro(grid_form_fields)%
<table class="cc_2by_grid_form_table" id="table_%(curr_form/form_id)%" cellspacing="0" cellpadding="0">
<tr>
<td class="cc_2by_grid_names med_bg"">
    %loop(curr_form/html_form_grid_rows,R)%
       <? $name_guess = _suck_out_grid_row_name($R); ?>
       <div id="dmit_%(#i_R)%" class="med_bg med_border"><a href="javascript://menu item" class="menu_item_title light_color" id="mit_%(#i_R)%" >%(#name_guess)%</a></div>
    %end_loop%
</td>
<td class="cc_2by_grid_fields light_bg">
<div id="fields_target_%(curr_form/form_id)%"></div>
<td>
</tr>
</table>
<script>

var gcols_%(curr_form/form_id)% = [
%loop(curr_form/html_form_grid_columns,C)%
        <? $fge = str_replace("'","\\'",$T->String($C['column_name'])); ?>
        '<span class="col">%(#fge)%</span>'%if_not_last(#C)%, %end_if%
%end_loop%
        ];

var grows_%(curr_form/form_id)% = [
%loop(curr_form/html_form_grid_rows,FR)%

        '<div id="%(curr_form/form_id)%_' + %(#i_FR)% + '" style="display:none">' +
   %loop(#FR/html_form_grid_fields,F)%
        %if_not_null(#F/form_grid_element)%
            <? $fge = str_replace("'","\\'",$F['form_grid_element']); ?>
            '<div class="f">' + gcols_%(curr_form/form_id)%[%(#i_F)% - 1] + ' %(#fge)%</div>' +
        %end_if%
   %end_loop% 
        '</div><div class="gform_breaker"></div>'%if_not_last(#FR)%, %end_if%
%end_loop%
];

new ccGridEditor('%(curr_form/form_id)%',grows_%(curr_form/form_id)%);

</script>
%end_macro%

<?

function _suck_out_grid_row_name(&$row)
{
    foreach( $row['html_form_grid_fields'] as $field )
    {
        $html = $field['form_grid_element'];
        if( strstr($html,'checkbox') || strstr($html,'select') )
            continue;
        if( preg_match('/value="([^"]+)"/',$html,$m) )
            return $m[1];
    }

    return 'hmmmm';
}

?>