%macro(form_fields)%
<table class="cc_form_table">
    %loop(curr_form/html_form_fields,F)%
        %if_not_empty(#F/form_error)%
            <tr class="cc_form_error_row"><td></td><td class="cc_form_error">%text(#F/form_error)%</td></tr>
        %end_if%
        <tr class="cc_form_row">
        <td  class="cc_form_label">
            %if_not_null(#F/label)%<div>%text(#F/label)%</div>%end_if%
            %if_not_null(#F/form_tip)%<span>%text(#F/form_tip)%</span>%end_if%</td>
        <td class="cc_form_element">%if_not_null(#F/macro)% %map(field,#F)%<!-- -->%call(#F/macro)% %end_if%<!-- -->%(#F/form_element)%</td></tr>
    %end_loop%
</table>
%end_macro%

%macro(stacked_form_fields)%
<table class="cc_form_table">
    %loop(curr_form/html_form_fields,F)%
        %if_not_empty(#F/form_error)%
            <tr class="cc_form_error_row"><td></td><td class="cc_form_error">%text(#F/form_error)%</td></tr>
        %end_if%
        <tr class="cc_form_row">
        <td  class="cc_form_label"><div>%text(#F/label)%</div><span>%text(#F/form_tip)%</span></td>
        </tr>
        <tr><td class="cc_form_element">%if_not_null(#F/macro)%  %map(field,#F)%<!-- -->%call(#F/macro)% %end_if%<!-- -->%(#F/form_element)%</td></tr></tr>
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
%map(post_form_goo,'form_fields.tpl/post_flat_grid_form')%
%end_macro%

%macro(grid_form_fields)%
<table class="cc_2by_grid_form_table" id="table_%(curr_form/form_id)%" cellspacing="0" cellpadding="0">
<tr>
<td class="cc_2by_grid_names med_bg" id="names_%(curr_form/form_id)%">
    %loop(curr_form/html_form_grid_rows,R)%
       <div id="dmit_%(#i_R)%" class="med_bg dark_border"><a href="javascript://menu item" class="menu_item_title light_color" id="mit_%(#i_R)%" >%(#R/name)%</a></div>
    %end_loop%
</td>
<td class="cc_2by_grid_fields light_bg" id="fields_%(curr_form/form_id)%">
%map(#C,curr_form/html_form_grid_columns)%
%loop(curr_form/html_form_grid_rows,FR)%
    <div id="%(curr_form/form_id)%_%(#i_FR)%" style="display:none">
   %loop(#FR/html_form_grid_fields,F)%
        %if_not_null(#F/form_grid_element)%
            <div class="f"><span class="col"><?= $C[$i_F-1]['column_name'] ?></span>%(#F/form_grid_element)%</div>
            <div class="gform_breaker"></div>
        %end_if%
   %end_loop% 
</div>
%end_loop%
</div>
<td>
</tr>
%if_not_null(curr_form/html_add_row_caption)%
<tr><td colspan="2">
    <a href="javascript://add a row" id="%(curr_form/form_id)%_adder">%(curr_form/html_add_row_caption)%</a>
    </td></tr>
%end_if%
</table>
<script> var %(curr_form/form_id)%_editor = new ccGridEditor('%(curr_form/form_id)%'); </script>
%map(post_form_goo,'form_fields.tpl/post_grid_form')%
%end_macro%

%macro(post_grid_form)%
    %if_empty(curr_form/html_meta_row)%
        %return%
    %end_if%
<div style="display:none">
%loop(curr_form/html_meta_row,meta)%
<div id="%(curr_form/form_id)%_meta_%(#i_meta)%">%(#meta)%</div>
%end_loop%
</div><!-- meta rows -->
<script>
%(curr_form/form_id)%_editor.cols = [
    %loop(curr_form/html_form_grid_columns,C)% '%(#C/column_name)%'%if_not_last(#C)%, %end_if% %end_loop%
    ];

</script>
%end_macro%

%macro(post_flat_grid_form)%
    %if_empty(curr_form/html_meta_row)%
        %return%
    %end_if%
<div style="display:none">
%loop(curr_form/html_meta_row,meta)%
<div id="%(curr_form/form_id)%_meta_%(#i_meta)%">%(#meta)%</div>
%end_loop%
</div><!-- meta rows -->
%if_not_null(curr_form/html_add_row_caption)%
    <button  onclick="do_add_row(); return false;">%text(curr_form/html_add_row_caption)%</button>
%end_if%
<script>
function do_add_row()
{
    add_flat_row( '%(curr_form/form_id)%', 
                   %(curr_form/html_form_grid_num_rows)%,
                   %(curr_form/html_form_grid_num_cols)%
                );
}
</script>
%end_macro%

%macro(select)%
<select id="%(field/name)%" name="%(field/name)%" %if_attr(field/class,class)%>
%map(#selval,field/value)%
%loop(field/options,opt)%
    <? $selected = ($k_opt == $selval) ? 'selected="selected"' : ''; ?>
    <option value="%(#k_opt)%" %(#selected)%>
        %text(#opt)%
    </option>
%end_loop%
</select>
%end_macro%

<?

function _suck_out_grid_row_name(&$row)
{
    foreach( $row['html_form_grid_fields'] as $field )
    {
        $html = $field['form_grid_element'];
        if( strstr($html,'checkbox') || strstr($html,'select') || strstr($html,'radio'))
            continue;
        if( preg_match('/value="([^"]+)"/',$html,$m) )
            return $m[1];
    }

    return 'hmmmm';
}

?>