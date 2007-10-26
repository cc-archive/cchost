

%%
    This is the main file for this skin, it's only called 
    when it is the selected skin in the system, so if you 
    want to hide macros from inherited skins put it here.

    otherwise it's a pretty boring file
%%

%macro(init)%
  %inherit( user_listing, round_box.tpl/user_profile )%        %% user profile page needs special handling for description field %%
  %call(html_head)%
  %call(main_body)%
%end_macro%

