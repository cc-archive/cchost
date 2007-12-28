
<style type="text/css">

#left_box {
     float: left;
     width: 440px;
     margin-left: 5%;
}

#right_box {
    float: left;
    width: 440px;
    margin-left: 5%;
}

p.peep {
  margin-left: 12px;
  font-size: 11px;
}

table.maintable td {
    vertical-align: top;
}

#left_quote, #right_quote  {
    padding:   4px;
    font-size: 40px; 
    font-family: 'Times New Roman',serif; 
    font-weight: bold; 
    text-align: left;
    vertical-align: top;
    width: 20px;
    color: #777733;
}

#right_quote {
    text-align: right;
}

#pull_quote {
    text-align: justify; 
    font-size: 11px;
}

#quote_credit {
    text-align: right;
    font-style: italic;
    margin: 0px 4px 35px 0px;
}

#quote_credit a {
    font-weight: normal;
    font-size: 10px;
}

#mixter_logo {
    float: left;
    margin: 10px 10px 0px 8px;
}

#mainbox { padding: 14px 30px 14px 14px; border: 1px solid #AAAA66; } 
</style>

<div style="text-align:center; padding:8px" >
<h1>Welcome to ccMixter</h1>
</div>
<div id="left_box">
    <div class="box">
        <img  id="mixter_logo" src="/mixter-files/skins/images/cc-mixter-sq-logo.png" alt="" border="0" />
        <table >
            <tr >
                <td  id="left_quote">&ldquo;</td>
                <td  id="pull_quote">
                    Make no mistake, ccMixter is the complete package. No other remix site commands the 
                    same level of respect amongst musicians, producers and content creators.
                </td>
                <td  id="right_quote">&rdquo;</td>
            </tr>
        </table>
        <p  id="quote_credit">
            <a  href="http://soundblog.spaces.live.com/?_c11_blogpart_blogpart=blogview&_c=blogpart&partqs=amonth%3d2%26ayear%3d2007">
                Dave's Imaginary Sound Spaces
            </a>
        </p>
        <p  class="peep">
            This is a community music site featuring remixes licensed under 
            <a  href="http://creativecommons.org/">Creative Commons</a>, where you can listen to, sample, mash-up, or 
            interact with music in whatever way you want.
        </p>
        <p class="peep">
            <b >Remixers</b>&nbsp;&nbsp;&nbsp;If you're into sampling, remixing and mash-ups grab the  
            <a  href="<?= $A['root-url']?>media/view/media/samples">sample packs</a> and <a  href="<?= $A['root-url']?>media/view/media/pells">a cappellas</a> for download and you can upload your version back into ccMixter, for others to enjoy and re-sample. All legal.
        </p>
        <p  class="peep">
        <b >Podcasters, directors and music lovers</b> &nbsp;&nbsp;&nbsp;
            If you're into music, browse this site to hear some of the 
            <a  href="<?= $A['root-url']?>media/view/media/picks">great remixes</a> people have built from sampling 
            music on this site, all licensed for use under Creative Commons license.
        </p>
    </div>

    <div class="box">
        <h2>Previous contests...</h2>
        <a  href="/djvadim">DJ Vadim</a><br  />
        <a  href="/salman">Salman Ahmad</a><br  />
        <a  href="/vieux">Vieux Farka Toure</a><br  />
        <a  href="/curve">(&copy;urve)&trade; </a><br  />
        <a  href="/ghostly">Christopher Willits</a><br  />
        <a  href="/fortminor">Fort Minor</a><br  />
        <a  href="/crammed">Crammed Discs</a> Cibelle, DJ Dolores, Apollo Nove<br  />
        <a  href="/copyrightcriminals">Copyright Criminals</a><br  />
        <a  href="/magnatune">Magnatune</a> Lisa Debendictis<br  />
        <a  href="/freestylemix">WIRED CD</a> Beastie Boys, Chuck D....<br  />
    </div>
    
</div> <!-- left_box -->
<div id="right_box" >
    <div class="box">
        <h2>DJ Vadim Album Tracks Released under CC</h2>
        <p>
            <a  href="<?= $A['root-url']?>bbe" class="logolink" style="margin:2px;float:left;width:175px;">
            <img src="<?= $T->URL('djvadim/bbe-logo.jpg'); ?>" border="0" style="border: 1px solid black;" /></a>
        </p>
        <p>
            <a  href="http://www.creativecommons.org">Creative Commons</a>, International superstar DJ Vadim and 
            <a  href="http://bbemusic.com">BBE</a> are pleased, (<i >very</i> pleased) to announce that all original solo, 
            individual instrumental and a cappella studio tracks for DJ Vadim's "Soundcatcher" album are now online under 
            a Creative Commons <a  href="http://creativecommons.org/licenses/by-nc/3.0">Attribution-NonCommercial</a> license, 
            so that producers worldwide can use the sounds in remixes and new compositions.
        </p>
        <p ><a  href="<?= $A['root-url']?>bbe">More information and where to download</a>...</p>
    </div>
    <div class="box">
        <h2>News from Friends</h2>
    </div>
</div> <!-- right box -->

