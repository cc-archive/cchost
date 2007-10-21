<div id="menu">

%loop(menu_groups,group)%
  <div class="menu_group">
    <p>%!var(#group/group_name)%</p>
    <ul>%loop(#group/menu_items,mi)%
      <li><a href="%!var(#mi/action)%" id="%!var_check(#mi/id)%">%!var(#mi/menu_text)%</a></li>
    %end_loop% </ul>
  </div>
%end_loop%

%loop(custom_macros,macro)%
<div class="menu_group">
  %call_macro($macro)%
</div>
%end_loop%

</div> <!-- end of menu -->
