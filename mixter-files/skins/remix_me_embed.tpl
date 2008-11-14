<?/*
[meta]
    type = ajax_component
    desc = _('Remix Me Embed')
    dataview = user_basic
    require_args = user
[/meta]
*/?>
%map(#R,records/0)%

<h1>Embed a "Remix Me" Widget In Your Web Page</h1>

<div style="width:450px;text-align:center;margin:14px auto;">
<p>Now you can encourage folks to remix your stems and a cappellas by embedding the following widget into your web site!
For other embed goodies, go to your <a href="<?= ccl('publicize', $R['user_name']); ?>">Publicize page</a>.
</p>

<div style="margin:0px auto;width:530px">
<script type="text/javascript" src="http://cchost.org/api/query?limit=50&template=mixter-files%2Fskins%2Fformats%2Fremix_me.tpl&chop=10&remixesof=%(#R/user_name)%&format=docwrite" ></script>
</div>

<p>Copy the following code into your web page:</p>

<textarea style="width:35em;height:10em;">
<script type="text/javascript" src="http://cchost.org/api/query?limit=50&template=mixter-files%2Fskins%2Fformats%2Fremix_me.tpl&chop=10&remixesof=%(#R/user_name)%&format=docwrite" ></script>
</textarea>

</div>