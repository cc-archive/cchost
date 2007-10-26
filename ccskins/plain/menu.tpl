
%if_not_empty(menu_groups)%

    <div id="menu">

    %loop(menu_groups,group)%
      <div class="menu_group">
        <p>%!var(#group/group_name)%</p>
        <ul>%loop(#group/menu_items,mi)%
          <li><a href="%!var(#mi/action)%" id="%!var_check(#mi/id)%">%!var(#mi/menu_text)%</a></li>
        %end_loop% </ul>
      </div>
    %end_loop%

    %% Get the custom sidebar items from settings() %%

    %settings(tmacs,custom_macros)%

    %% These are little strange, the value is the flag
       that decides what to print, the key is the macro
    %%

    %loop(custom_macros,flag)%
      %if_not_null(#flag)%
        <div class="menu_group">        
          %call_macro(#key_flag)%
        </div>
      %end_if%
    %end_loop%

    </div> <!-- end of menu -->

%end_if%