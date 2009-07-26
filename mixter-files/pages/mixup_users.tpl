<!--
%%
[meta]
    name = mixup_users
    type = template_component
    desc = _('Display users for a mixups')
    dataview = mixup_users
[/meta]

%%
-->
<!-- template mixup_users -->

<?
    $NC = 4;
    $W = 100 / $NC;
    $rows = array_chunk($A['records'], $NC);
    
?>
<table>
%loop(#rows,cols)%
<tr>
  %loop(#cols,R)%
  <td  style="vertical-align:bottom;width:110px;text-align:center;padding:3px;">
     <div class="box miximgbox" style="height:120px" >
      <a class="hidemixup" href="%(#R/mixer_page_url)%"><img src="%(#R/mixer_avatar_url)%" /></a>
      <br class="hidemixup" />
      <a href="%(#R/mixer_page_url)%">%(#R/mixer_name)%</a>
      </div>
  </td>
  %end_loop%
</tr>
%end_loop%
</table>
