<?


function _t_util_print_html_content($T,&$A)
{
    if( empty($A['html_content']) )
        return;

    foreach( $A['html_content'] as $html )
        print $html;
}

function _t_util_print_forms($T,&$A)
{
    if( empty($A['forms']) )
        return;

    foreach( $A['forms'] as $form_info )
    {
        $A['curr_form'] = $form_info[1];
        $T->Call($form_info[0]);
    }
}


?>