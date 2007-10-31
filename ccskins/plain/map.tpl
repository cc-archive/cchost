

%%
    suck in shared template stuff
    -----------------------------
%%
%import_skin(ccskins/shared)%
%import_skin(ccskins/shared/formats)%

%%
    Customize
    ---------
%%
%append( end_script_blocks, basic_stuff.php/post_script)%
<? require_once('ccskins/plain/strings.php'); ?>  %% hmmm, probably need to formalize this a little better %%

%%
    define our page and form stuff
    ------------------------------
%%
%map( html_head,         'head.tpl/html_head' )%
%map( print_head_links,  'head.tpl/print_head_links' )%

%map( main_body,         'body.tpl/main_body' )%
%map( print_page_title,  'body.tpl/print_page_title' )%
%map( print_banner,      'body.tpl/print_banner' )%
%map( print_tabs,        'body.tpl/print_tabs' )%
%map( print_sub_nav_tabs,'body.tpl/print_sub_nav_tabs' )%
%map( print_footer,      'body.tpl/print_footer' )%
%map( print_end_script_blocks, 'body.tpl/print_end_script_blocks' )%

%map( print_menu, 'menu.tpl' )%

%map( html_form,       'html_form.php/html_form' )%
%map( form_fields,     'html_form.php/form_fields' )%
%map( grid_form_fields,'html_form.php/grid_form_fields' )%
%map( submit_forms,    'html_form.php/submit_forms' )%
%map( show_form_about, 'html_form.php/show_form_about' )%

%map( print_bread_crumbs, 'basic_stuff.php/print_bread_crumbs' )%
%map( print_client_menu,  'basic_stuff.php/print_client_menu' )%
%map( prev_next_links,    'basic_stuff.php/prev_next_links' )%

