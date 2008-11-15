 %%
[meta]
 type = format
 dataview = user_basic
 required_args = user
[/meta]
%%
%map(#R,records/0)%
<style>
#remix_me_doc p {
    font-size:14px; 
 }
</style>
<h1>Uploading Your %(#R/user_real_name)% Remix</h1>
<div id="remix_me_doc" style="padding-left:13%">
<p>First off, thanks for remixing %(#R/user_real_name)%!</p>
%if_not_null(logged_in_as)%
<p>Since you're already logged in, go straight to the <a href="<?= ccl('submit','remix') ?>">Submit A Remix</a> form...</p>
%else%
<p>In order to upload your remix you need to have an account with %(site-title)%.</p>
<p>If you already have one, great, <a href="<?= ccl('login') ?>">log in</a> and click on 'Submit Files' in the <b>Artists</b> menu.</p>
<p>If you don't have an account with us then by all means <a href="<?= ccl('register') ?>">create one now</a>. It's easy and free.</p>
<h2>Thanks! And welcome to the <a href="http://creativecommons.org">Creative Commons</a> Sample Pool!</h2>
%end_if%
</div>
