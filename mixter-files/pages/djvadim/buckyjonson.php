
<div style="font-family: Verdana; font-size:11px">
<style>
.bbemsg {
  color: #944;
}
</style>

<h1>BBE Music and ccMixter presents: Bucky Jonson</h1>

<div style="width:60%;float:left;">
    <div class="box">


<div style="float: right; margin: 8px;" >
<a href="http://bbemusic.com/"><img src="/mixter-files/pages/djvadim/bbe-logo.jpg" /></a>
<br />
<a href="http://creativecommons.org"><img src="/mixter-files/images/cc-logo.png" /></a>
</div>
<p>
<?= file_get_contents('mixter-files/pages/djvadim/bucky_1.txt') ?>
</p>
</div>
</div>

<div style="padding: 20px 0px 20px 0px;">

<img src='/mixter-files/pages/djvadim/bucky_logo.png' style="float:right;margin:13px" />

<h3 style="text-align: left">Remixers</h3>


<p style="font-size:13px">
<?= file_get_contents('mixter-files/pages/djvadim/bucky_2.txt') ?>
</p>

<div id="sources">
<span class="bbemsg" ><?= file_get_contents('mixter-files/pages/djvadim/bucky_3.txt') ?></span>
</div>

<p>
  NOTE: we have <a href="<?= $A['home-url'] ?>thread/611">strict policies about copyright material</a>. No wink-wink. Violators
  will be banned from the site.
</p>

</div>

<script>

function showBBESources( resp )
{
    $$('sources').innerHTML = resp.responseText;
}

function getSources()
{
    try
    {
      var url = root_url + 'mixter-lib/mixter-bucky.php?bucky_format=1';
      var myAjax = new Ajax.Request( url, { onComplete: showBBESources, method: 'get'} );
    }
    catch (e)
    {
      $$('sources').innerHTML = 'error';
    }
}


getSources();

</script>

</div>
<div style="float:left;margin-top:13px;margin-left:20px;">
<img src="/mixter-files/pages/djvadim/bucky_portraits.png" />
</div>
</div>