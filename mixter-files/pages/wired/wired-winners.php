<?
if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

function _t_wired_winners_init($T,&$targs) {
    
}
?><style >
p { font-size: 12px; }
h2,h3 { margin: 2px; }
.uploadcredits { margin-bottom: 13px; }
</style>
<h1 >The Fine Art of Sampling Contest Winners</h1>
<img  src="/mixter-files/wired-cd.gif" width="150" height="148" style="float:right;margin-left:10px;margin-bottom:10px;border:1px solid #ccc;" />
<p  style="margin-bottom:30px;">From December 2004 to February 2005, we ran <a  href="/freestylemix/view/contest/about">The Fine Art of Sampling Contests</a> here at CC Mixter, based on tracks from <a  href="http://creativecommons.org/wired/">The WIRED CD -- Rip. Mix. Sample. Mash. Share.</a></p>
<div  style="width:135px;padding:0px;">
<div  class="cc_podcast_link"><a  href="<?= $A['home-url']?>podcast/page?tags=winner,freestylemix">PODCAST Winners</a></div>
<div  class="cc_stream_page_link"><a  href="<?= $A['home-url']?>stream/page?tags=winner,freestylemix">STREAM Winners</a></div>
</div>
<br  clear="both" />
<div  style="float:left; width:45%;">
<div  style="padding-right:10px;">
<h2  style="font-size:18px;margin:0px;font-weight:normal;">The Freestyle Mix Contest Winners</h2>
<p >Winners appeared on a Creative Commons produced CD called The Wired CD -- Ripped. Mixed. Sampled. Mashed. Shared. Here are the winners in no particular order:</p>
<?
$A['wrecords'] = CC_tag_query('winner,freestylemix', 'all' );

$carr101 = $A['wrecords'];
$cc101= count( $carr101);
$ck101= array_keys( $carr101);
for( $ci101= 0; $ci101< $cc101; ++$ci101)
{ 
   $A['w'] = $carr101[ $ck101[ $ci101 ] ];
   
?><h3 ><a  class="cc_file_link" href="<?= $A['w']['file_page_url']?>"><?= $A['w']['upload_name']?></a></h3>
<div  class="uploadcredits">by <a class="cc_user_link" href="<?= $A['w']['user_page_url']?>"><?= $A['w']['user_real_name']?></a></div>
<?
} // END: for loop

?><p  style="color:#999;"><strong >Freestyle Mix judges</strong>: WIRED music editors and contributors Eric Steuer, Philip Sherburne, Adrienne Day, Hua Hsu, Geeta Dayal.</p>
</div>
</div>
<div  style="float:left; width:53%;border-left:1px solid #eee;">
<div  style="padding-left:20px;">
<h2  style="font-size:18px;margin:0px;font-weight:normal;">The Militia Mix Contest Winner</h2>
<p >The Militia Mix winner gets to appear as a bonus track on The Fine Arts Militia's next CD featuring Chuck D. The winner is:</p>
<h2 ><a  href="/file/norelpref/1">On Meaning On</a></h2>
<div  class="uploadcredits">by <a  href="/by/norelpref">heavyconfetti</a></div>
<p  style="color:#999;"><strong >Militia Mix judges</strong>: Brian Hardgroove, of the Fine Arts Militia, and Scott Egbert of GigAmerica.</p>
</div>
</div>