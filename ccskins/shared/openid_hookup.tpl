
<div class="box"
<p>
You are logging in using your verfied OpenID: <b>%(openid_info/openid)%</b>
</p>

<p>
The information you've given us is nickname: <b>%(openid_info/nickname)%</b> 
at email address: <b>%(openid_info/email)%</b>
</p>
</div>
<form action="<? ccl('openid','match') ?>" method="post">
%map(oidchk,'"checked=1"')%
%loop(openid_info/matches,M)%
  <input type="radio" %(oidchk)% name="match" id="match_%(#M/user_id)%" value="%(#M/user_id)%" />
  <label for="match_%(#M/user_id)%">%(#M/display_name)% (%(#M/user_email)%)</label>
  %map(oidchk,'')% <br />
%end_loop%
<input type="radio" %(oidchk)% name="match" id="other_record" value="other"><label for="other">Log in as existing user:</label><br />
Existing user name: <input type="text" name="user_name" id="user_name" /><br />
Password: <input type="password" name="user_password" /><br />
<input type="radio" name="match" id="new_record" value="other"><label for="other">Create a new user:</label><br />
New user name: <input type="text" name="user_name" id="user_name" value="%(openid_info/nickname)%"/><br />
New user full name: <input type="text" name="user_name" id="user_name" value="%(openid_info/fullname)%"/><br />

<input type="submit" value="Submit OpenID Info" />
</form>
