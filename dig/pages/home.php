<?
/*
* Artistech Media has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file LICENSE.TXT.
* 
* You may use dig.ccMixter software in accordance with the
* terms of that license. You agree that you are solely 
* responsible for your use of dig.ccMixter software and you
* represent and warrant to Artistech Media that your use
* of dig.ccMixter software will comply with the CC-GNU-GPL.
*
* $Id$
*
*/

	$page_title = 'dig.ccmixter &ldquo;You already have permission&hellip;&rdquo;';
	$home_class = 'class="current"';
	
	require_once('lib/head.php');
?>
	<div id="content">
		<div class="page full home-heading" id="homepage">
			<div class="block wider first">
				<h2>ccMixter Music Discovery Site</h2>
				<h3 class="subheading">&ldquo;You already have permission&hellip;&rdquo;</h3>
			</div>
			<div class="block wider">
				<form class="dig-entry round" action="/dig" method="get">
					<img src="images/start-digging.png" alt="Start digging" id="entry-label" />
					<div class="entry-input-container round"><input type="text" name="search-query" value="" id="q" /></div>
					<div class="entry-button-container">
						<input id="entry-search" type="image" alt="dig" src="images/search-button-bg.png" />
					</div>
					<div class="clearer"></div>
				</form>
			</div>
		</div>
		
		<div class="page full home-description">
			<div class="block widest first">
				<p><strong>dig.ccmixter</strong> is divided into several sections to get you going:</p>
				<div class="block first">
					<h3>Podsafe Music</h3>
				    <p>Great vocalists meet fresh, innovative producers every day on ccMixter. Together
				    they make radio-ready tracks and remixes&hellip; <a href="/podcast_music">Search for podsafe music&hellip;</a></p>
				</div>
				<div class="block">
					<h3>Instrumental Music for Film, YouTube&trade; Videos and Soundtracks</h3>
				    <p>Instrumental background or theme music freely available&hellip; <a href="/music_for_film_and_video">Search for instrumental music for videos&hellip;</a></p>
				</div>
				<div class="block">
					<h3>Music for iPods, Dance Party, Cubicle, Drive-time Enjoyment</h3>
				    <p>Fresh hot playlists, pre-made podcasts, editors' picks are hand curated by staff and DJs, ready for your enjoyment&hellip; <a href="/featured">Download or stream our curators' picks&hellip;</a></p>
				</div>
				<div class="clearer"></div>
				<div class="block first">
					<h3>Free Music for Commercial Projects</h3>
				    <p>If you need free music for a commercial project, we can accommodate&hellip; <a href="/free_music">Find music that is free for commercial use</a></p>
				</div>
				<div class="block wider">
					<h3>For Musicians</h3>
				    <p>All of the music on dig.ccmixter was created especially for this site. We don't take your back-catalogue
				    or original music you already have on your hard disk (!) We want to know what new things you can do with our
				    2,000 original a cappellas and 10,000 samples (that are all licensed under Creative Commons). If you're
				    up for a new way of thinking about creative collaboration then come join us at <a href="http://ccmixter.org">ccMixter.org</a>.</p>
				</div>
				<div class="clearer"></div>
			</div>
			<div class="block badge-container round">
					<div class="badge">
						<a href="http://ccmixter.org"><img src="images/ccmixter.jpg" alt="Visit ccmixter"></a>
					</div>
					<div class="badge">
						<a href="http://tunetrack.net"><img src="images/tunetrack.jpg" alt="Visit TuneTrack"></a>
					</div>
					<div class="badge">
						<a href="http://artistechmedia.com"><img src="images/artistech.jpg" alt="Visit ArtisTech Media"></a>
					</div>
					<div class="badge">
						<a href="http://creativecommons.org"><img src="images/cc.jpg" alt="Visit Creative Commons"></a>
					</div>
			</div>
			<div class="clearer"></div>

		</div>
	<? require_once('lib/footer.php'); ?>
	</div>
  </div>
</body>
</html>