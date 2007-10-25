
%import_skin(ccskins/plain)%  %% this skin is based on the 'plain' skin %%

%macro(init)%
  %inherit( user_listing, round_box.tpl/user_profile )%      %% hook the user profile page %%
  %call(html_head)%
  %call(main_body)%
%end_macro%

