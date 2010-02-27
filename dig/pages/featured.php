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
	$page_title = 'dig.ccmixter Featured Music';
	$featured_class = 'class="current"';
				
	require_once('lib/head.php');
?>
	<div id="content">
		<div class="page full">
			<h2>Featured</h2>
			<div id="featured">
				<!-- <div id="debug"></div> -->
				<div id="results">
					<div class="block wider first" id="edpicks"></div>
					<div class="block wider" id="popchart"></div>
					<div class="clearer"></div>					
					<div id="podcasts"></div>
				</div>
			</div>
		</div>
	<? require_once('lib/footer.php'); ?>
	</div>
  </div>
</body>
</html>