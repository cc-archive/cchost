

%%
    This is the main file for this skin, it's only called 
    when it is the selected skin in the system, so if you 
    want to hide macros from inherited skins put it here.

    otherwise it's a pretty boring file
%%

%append( style_sheets, css/plain.css)%
%append( end_script_blocks, plain.tpl/disable_tabs )%     %% add our script block to bottom of every page %%

%macro(init)%
  %call(html_head)%
  %call(main_body)%
%end_macro%
