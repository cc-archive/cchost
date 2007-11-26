%%
[meta]
    type     = template_component
    desc     = _('used by remix search')
[/meta]
%%
%loop(records,R)%
   <div class="remix_check_line" id="rl_%(#R/upload_id)%" >
     <input class="remix_checks" type='checkbox' name='remix_sources[%(#R/upload_id)%]' id='src_%(#R/upload_id)%'  /> <span id="rc_%(#R/upload_id)%">
     <span class="upload_name">%chop(#R/upload_name,30)%</span></span> %text(str_by)%
     <span class="artist_name">%chop(#R/user_real_name,23)% (%(#R/user_name)%)</span>
   </div>
%end_loop%
