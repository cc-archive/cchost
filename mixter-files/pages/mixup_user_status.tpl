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

<style>
#mixup_status td {
    padding-left: 10px;
    padding-bottom: 5px;
}
.mixup_status_ {
    color: red;
}
.mixup_status_1 {
    color: green;
}
.mixup_status_2 {
    color: yellow;
    background-color: #999;
}
.mixup_status_3 {
    background: yellow;
    color: red;
}
.mixstat {
    text-align: center;
}
</style>
<table id="mixup_status">
%loop(records,R)%
<tr>
  <td style="text-align: right" >
      <a href="%(#R/mixer_page_url)%">%(#R/mixer_name)%</a>
  </td>
  <td class="mixup_status_%(#R/mixup_user_confirmed)% mixstat">
    <span>
    %switch(#R/mixup_user_confirmed)%
        %case(0)%
            unconfirmed
        %end_case%
        %case(1)%
            done
        %end_case%
        %case(2)%
            not sure
        %end_case%
        %case(3)%
            won't finish
        %end_case%
    %end_switch%
    </span>    
  </td>
  <td><span style="font-size: 80%" href="%(#R/mixee_page_url)%">%(#R/mixee_name)%</span>
  </td>
</tr>
%end_loop%
</table>
