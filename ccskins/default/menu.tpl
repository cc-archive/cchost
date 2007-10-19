<div id="menu">

%loop(menu_groups,group)%
  <div class="menu_group">
    <p>%item(group,group_name)%</p>
    <ul>%loop_item(group,menu_items,mi)%
      <li><a href="%item(mi,action)%" id="%not_empty_item(mi,id)%">%item(mi,menu_text)%</a></li>
    %end_loop% </ul>
  </div>
%end_loop%

%loop(custom_macros,macro)%
<div class="menu_group">
  %call_macroi($macro)%
</div>
%end_loop%

</div> <!-- end of menu -->
