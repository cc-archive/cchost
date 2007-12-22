<?
if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');
?><div  style="font-family: Verdana; font-size:11px">
<style >
.bbemsg {
  color: #944;
}
</style>
<h1 >BBE Music and ccMixter</h1>

<? $bbet = file_get_contents($T->Search('bbe_1.txt')); ?>

<div style="width: 540px;margin-right: auto;margin-left: auto;">
<div class="box"> 
    <div style="float: right; margin: 8px;">
        <a href="http://bbemusic.com/"><img  src="<?= $T->URL('bbe-logo.jpg'); ?>" /></a>
        <br/>
        <a  href="http://creativecommons.org"><img  src="<?= $T->UserGraphic('cc-logo.png'); ?>" /></a>
    </div>
    <p><?= $bbet ?></p>
</div>

<? $bbet = file_get_contents($T->Search('bbe_2.txt')); ?>

<div style="background: url(<?= $T->URL('sc_cover_faded.jpg') ?>) repeat-y  top right; 
            padding: 20px 110px 20px 0px;">
    <h3 style="text-align: left">Remixers</h3>
    <p style="font-size:13px"><?= $bbet ?></p>
    <? $bbet = file_get_contents($T->Search('bbe_3.txt')); ?>
    <div  id="sources">
        <span  class="bbemsg"><?= $bbet?></span>
    </div>
    <p >
      NOTE: we have <a  href="http://ccmixter.org/media/thread/611">strict policies about copyright material</a>. No wink-wink. Violators
      will be banned from the site.
    </p>
</div>

</div>

<script>

function showBBESources( resp )
{
    $('sources').innerHTML = resp.responseText;
}

function getSources()
{
    try
    {
      var url = root_url + 'mixter-lib/mixter-bbe.php?bbe_format=1';
      var myAjax = new Ajax.Request( url, { onComplete: showBBESources, method: 'get'} );
      $('sources').innerHTML = '<span  class="bbemsg"><?= $bbet ?></span>';
    }
    catch (e)
    {
      $('sources').innerHTML = 'error';
    }
}


getSources();

</script>
</div>
</div>