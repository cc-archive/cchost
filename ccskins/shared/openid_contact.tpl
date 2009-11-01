<div class="box" style="width:50%">
%if_null(contact_info)% <!-- message -->
%text(str_openid_contact_short)%
%else% <!-- message 2 -->
%text(str_openid_contact_disc)%
%end_if%
<ul>
%loop(contact_info,CI)%
<li><a href="%(#CI)%" class="cc_openid_link">%(#CI)%</a></li>
%end_loop%
</ul>
</div>
