
%import_skin(ccskins/plain)%  %% this skin is based on the 'plain' skin %%
%append( style_sheets, 'css/plain.css' )%
%append( end_script_blocks, plain.tpl/disable_tabs )%     %% plain requires this line %%


%macro(init)%
  %inherit( user_listing, round_box.tpl/user_profile )%        %% user profile page needs special handling for description field %%
  %call(html_head)%
  %call(main_body)%
%end_macro%

