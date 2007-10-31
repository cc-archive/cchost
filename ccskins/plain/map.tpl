

%import_skin(ccskins/shared)%
%import_skin(ccskins/shared/formats)%

<? require_once('ccskins/plain/strings.php'); ?>  %% hmmm, probably need to formalize this a little better %%

%map( html_form,       'html_form.php/html_form' )%
%map( form_fields,     'html_form.php/form_fields' )%
%map( grid_form_fields,'html_form.php/grid_form_fields' )%
%map( submit_forms,    'html_form.php/submit_forms' )%
%map( show_form_about, 'html_form.php/show_form_about' )%

<?
function page_tab_helper($tabs,$id)
{
    print "<ul id=\"{$id}\" >";
    foreach( $tabs as $tab )
    {
        $selected = empty($tab['selected']) ? '' : 'class="selected_tab"';
        print "<li $selected><a href=\"{$tab['url']}\" title=\"{$tab['help']}\"><span>{$tab['text']}</span></a></li>";
    }
    print '</ul>';
}

function page_script_link_helper($links,$T)
{
    foreach( $links as $script_link ) {
        if( substr($script_link,0,7) == 'http://' )
            $path = $script_link;
        else {
            $path = $T->Search($script_link);
            if( empty($path) ) die( "Can't find script '$script_link'" );
            $path = ccd($path);
        }
        print "<script type=\"text/javascript\" src=\"${path}\" ></script>\n";
    }
}
?>
