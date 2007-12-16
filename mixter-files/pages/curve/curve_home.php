<?
if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

function _t_curve_home_init($T,&$targs) {
    
}
?><style > 
  h2 { margin-top: 25px; }
</style>
<h1  id="ccttite">Creative Commons ((c)urve)music tm Remix Contest</h1>
<div  style="margin-right: 5%">
<div  style="margin: 1px;float:right;width:395px;"><img  src="<?= $T->URL('tamy.png'); ?>" /></div>
<p >
<a  href="http://www.creativecommons.org">Creative Commons</a> and 
    <a  href="http://curvemusic.net">(&copy;urve)music&trade;</a> (the &trade; is for 'Talent Management') are pleased 
    to offer the audio source 
    files from several tracks from Zone's 'MADRUGADA' and Tamy's 'Sou Mais Bossa' albums online under a 
    Creative Commons <a  href="http://creativecommons.org/licenses/by-nc/3.0">Attribution-NonCommercial</a> license, 
    so that producers worldwide can use the sounds in remixes and new compositions. As a way to celebrate we
    are sponsoring a remix contest using those sources.
  </p>
<h2 >How to Participate</h2>
<p >
      Create an account here at ccMixter.org. Then upload your entries using the contest submit forms 
      between 2:00 a.m. CDT on June 6, 2007 until 11:00 p.m. CDT on June 28, 2007. (<a  href="http://www.timeanddate.com/library/abbreviations/timezones/na/cdt.html">CDT timezone info</a>)
    </p>
<p >
      Source Materials: <a  href="<?= $A['root-url']?>curve/view/contest/sources"><b >Download</b></a> the a cappellas and 
      other separated audio elements.
    </p>
<h2 >Prize</h2>
<p >
  After all eligible entries have been received, the best remixes will be included on two remix albums to be released later this year. 
</p>
<h2 >About Tamy</h2>
<p > Tamy's music is Bossa Nova with a 21st Century twist. Hailing from Vitoria, 
Brazil, the singer/songwriter mixes MPB (Musica Popular Brasileira) with electronic beats, croons 
to the swing of Rio's famous samba-funk and sings softly alongside Afro-Brazilian grooves. Add to the 
mix her melodic vocal arrangements and a danceable beat and you have something everyone can appreciate.</p>
<br  style="clear:right" />
<h2 >About Zone</h2>
<div  style="">
<div  style="margin-right: 5px;float:left">
<img  src="<?= $T->URL('zone.png'); ?>" />
</div>
<div  style="margin-left: 7px;float:right">
<img  src="<?= $T->URL('manola.png'); ?>" />
</div>
<p > 
    Enzo Torregrossa AKA ZONE, is among the very best modern jazz players in the world. And, lucky for us, the Italian bass 
    player, who has performed alongside masters Dizzie Gillespie and Kenneth Jackson, is back in action. 
  </p>
<p > 
On MADRUGADA, ZONE explores his newly found identity on technology mixing it with trademark live arrangements, cleverly mounding contemporary jazz into dance culture. Breaking new grounds in modern Latin music via songs like PENSO EM MIM (feat. singer/songwriter - MANOLA MICALIZZI), ZONE places himself high next to the peers that inspired him in the first place.</p>
<p  style="clear:both"> "Zone has meticulously created an intriguing fusion of jazz and dance music to yield a unique melody the jazz scene hasn’t yet tasted. The Italian bass player has made comrades of two very different genres of music, and collided what most would consider polar opposites to combine into musical eloquence. Zone will most notably be recognized for a risky marriage of two seemingly distant musical expressions and making one great sound." 
    <i >The Inside Connection Magazine - USA</i>
</p>
</div>
<br  style="clear:right" />
<h2 >From (&copy;urve)music&trade;</h2>
<table  style="vertical-align: center;">
<tr ><td >
<img  style="margin-right:11px;" src="<?= $T->URL('curve_logo.png'); ?>" alt="[ (&copy;urve)music&trade; ]" />
</td><td >
<p > "It is with great pleasure we announce this remix contest alongside ccMixter. We hope with your help to continue drawing the (&copy;urve) around new ideas for the delivery of digital content; contribute to the debate of sharing knowledge to enhance awareness of unknown artists, while also offering our token of appreciation and belief of the work carried out by the Creative Commons and similar organizations." </p>
<p  style="text-align: right; font-style: italic">Afonso Marcondes, (&copy;urve)music&trade;</p>
</td></tr>
</table>
<br  style="clear:left;margin-bottom:14px;" />
<h2 >Official Rules</h2>
<p >Read the <a  href="<?= $A['root-url']?>curve/view/contest/rules">Official Rules</a>.</p>
</div>
<script >

function settitle()
{
   var sbuck = $('ccttite');
  sbuck.innerHTML = 'Creative Commons (&copy;urve)music&trade; Remix Contest';
}

setTimeout( settitle, '1' );

</script>