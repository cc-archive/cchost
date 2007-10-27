

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
%map( html_head,         'page.tpl/html_head' )%
%map( main_body,         'page.tpl/main_body' )%
%map( print_head_links,  'page.tpl/print_head_links' )%
%map( print_page_title,  'page.tpl/print_page_title' )%
%map( print_banner,      'page.tpl/print_banner' )%
%map( print_tabs,        'page.tpl/print_tabs' )%
%map( print_sub_nav_tabs,'page.tpl/print_sub_nav_tabs' )%
%map( print_footer,      'page.tpl/print_footer' )%
%map( print_end_script_blocks, 'page.tpl/print_end_script_blocks' )%
%map( print_menu, 'menu.tpl' )%
%map( html_form,       'form.php/html_form' )%
%map( form_fields,     'form.php/form_fields' )%
%map( grid_form_fields,'form.php/grid_form_fields' )%
%map( submit_forms,    'form.php/submit_forms' )%
%map( show_form_about, 'form.php/show_form_about' )%
%map( show_prompts,       'basic_stuff.php/print_prompts' )%
%map( print_bread_crumbs, 'basic_stuff.php/print_bread_crumbs' )%
%map( print_client_menu,  'basic_stuff.php/print_client_menu' )%
%map( prev_next_links,    'basic_stuff.php/prev_next_links' )%

