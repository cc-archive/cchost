
%%
    suck in shared template stuff
    -----------------------------
%%
%import_map(ccskins/shared)%
%import_map(ccskins/shared/formats)%

%%
    Add our round boxing to shared stuff
%%
%append(script_links,'js/round-box.js' )%
%append(style_sheets,'css/round-box.css' )%

%php(require_once('ccskins/default/strings.php'))%  %% hmmm, probably need to formalize this a little better %%

%%
    define our page and form stuff
    ------------------------------
%%
%map( html_head,         'page.php/html_head' )%
%map( main_body,         'page.php/main_body' )%
%map( print_head_links,  'page.php/print_head_links' )%
%map( print_page_title,  'page.php/print_page_title' )%
%map( print_banner,      'page.php/print_banner' )%
%map( print_tabs,        'page.php/print_tabs' )%
%map( print_sub_nav_tabs,'page.php/print_sub_nav_tabs' )%
%map( print_footer,      'page.php/print_footer' )%
%map( print_end_script_blocks, 'page.php/print_end_script_blocks' )%

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

