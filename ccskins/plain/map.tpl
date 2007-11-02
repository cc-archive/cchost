

%import_skin(ccskins/shared)%
%import_skin(ccskins/shared/formats)%

<? require_once('ccskins/plain/strings.php'); ?>  %% hmmm, probably need to formalize this a little better %%

%map( html_form,       'html_form.php/html_form' )%
%map( form_fields,     'html_form.php/form_fields' )%
%map( grid_form_fields,'html_form.php/grid_form_fields' )%
%map( submit_forms,    'html_form.php/submit_forms' )%
%map( show_form_about, 'html_form.php/show_form_about' )%
