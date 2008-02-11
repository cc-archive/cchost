<style>
#inner_content {
    width: 550px;
    margin: 30px auto;
}
.fslist li {
  margin-bottom: 13px;
}
.fsimg {
  padding: 8px;
} 
</style>

<div  class="box">
    <h2>the freesound project &amp; ccMixter</h2>
        <div  style="float: right; margin: 8px;"><a  href="http://freesound.iua.upf.edu/">
            <img  src="<?= $T->URL('freesound-logo.gif'); ?>" /></a>
        </div>
        <p >The <a  href="http://freesound.iua.upf.edu/">freesound project</a> is an independent
        audio sample collection website that uses Creative Commons licensing to share 10,000s
        of sample for use in audio works. Think of this way: freesound is to samples as ccMixter
        is to remixes. (The freesound project and its website are not associated with Creative
        Commons or ccMixter. We just like 'em a whole lot.)</p>
</div>

<h3 style="text-align: left">Sound Designers</h3>
<p>
    While ccMixter accepts sample uploads, it's not our specialty so we strongly 
    encourage sound designers to consider uploading to 
    <a  href="http://freesound.iua.upf.edu/">freesound</a>. Your samples will still be 
    available for remixing here and you'll get 100% of the same attribution for every
    remix that is uploaded here. We even keep track of 
    <a  href="<?= $A['home-url']?>pools/pool/4">who's remixed you...</a>
</p>

<h3 style="text-align: left">Remixers</h3>
<p >The Creative Commons Sample Pool just grew by over 20,000 samples thanks to the 
freesound project and ccMixter working together to act as one big sample and remix consortium.
</p>

<p>Here are the steps to use the two sites together:</p>

<ul  class="fslist">
    <li >
        Create an account at the <a  href="http://freesound.iua.upf.edu/">freesound project web site</a>.
    </li>
    <li>
        Browse and download from the 20,000 samples found there. (Make sure to note which
        artists and samples you actually used in your project.)
    </li>
    <li>
        Create a remix using those sounds and mix in your own sounds and any other Creative Commons licensed
        or otherwise legally safe samples.
    </li>
    <li >
        Create an account <a  href="<?= $A['root-url']?>register">here at ccMixter</a>.
    </li>
    <li>
        Upload your remix here at <b >ccMixter</b> using the <a  href="<?= $A['home-url']?>submit/remix">Submit Remix 
        form</a> and searching through the Freesound sample pool for the samples you used.
    </li>
</ul>

</div>
