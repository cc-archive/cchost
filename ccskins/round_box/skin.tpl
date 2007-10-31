
%import_skin(ccskins/plain)%  %% this skin is based on the 'plain' skin %%
%append( style_sheets, 'css/plain.css' )%
%append( end_script_blocks, plain.tpl/disable_tabs )%     %% plain requires this line %%

%if(ajax)%
    %call('short_page.tpl')%
%else%
%inherit( user_listing, round_box.tpl/user_profile )%        %% user profile page needs special handling for description field %%
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 //EN">
<html>
    %call('head.tpl')%
    %if(show_body_header)%
        %call('body.tpl')%
    %else%
        %call('short_page.tpl')%
    %end_if%
</html>
%end_if%

