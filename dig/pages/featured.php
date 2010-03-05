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

require_once('lib/query.php');

$query_args = array(
    'dataview' => 'diginfo',
    'tags'     => 'editorial_pick',
    'limit'    => 6,
);

$queries['edpicks'] = new digQuery();
$queries['edpicks']->ProcessAdminArgs($query_args);
$queries['edpicks']->Query();

$queries['edpicks']->_page_opts['parent'] = '#edpicks';
$queries['edpicks']->_page_opts['results_func'] = 'edpickQueryResults';


$query_args = array(
    'dataview' => 'diginfo',
    'tags'     => 'remix',
    'sort'     => 'rank',
    'sinced'   => '2 weeks ago',
    'limit'    => 6
);

$queries['popular'] = new digQuery();
$queries['popular']->ProcessAdminArgs($query_args);
$queries['popular']->Query();

$queries['popular']->_page_opts['parent'] = '#popular';
$queries['popular']->_page_opts['results_func'] = 'popchartQueryResults';


$query_args = array(
    'dataview' => 'topics_podinfo',
    'type'     => 'podcast',
    'limit'    => 10,
    'offset'   => 1
);

$queries['podcasts'] = new digQuery();
$queries['podcasts']->ProcessAdminArgs($query_args);
$queries['podcasts']->Query();

$queries['podcasts']->_page_opts['parent'] = '#podcasts';
$queries['podcasts']->_page_opts['results_func'] = 'podcastQueryResults';

$script_heads[] = queries_to_jscript( $queries );
$page_title = 'dig.ccmixter Featured Music';
$featured_class = 'class="current"';
            
require_once('lib/head.php');
?>
	<div id="content">
		<div class="page full">
			<h2>Featured</h2>
			<div id="featured">
				<!-- div id="debug"></div -->
				<div id="results">
					<div class="block wider first" id="edpicks"></div>
					<div class="block wider" id="popchart"></div>
					<div class="clearer"></div>					
					<div id="podcasts"></div>
				</div>
			</div>
            <div id="no_script_results">
            <? queries_to_no_script($queries); ?>
            </div>
		</div>
	<? require_once('lib/footer.php'); ?>
	</div>
  </div>
</body>
</html>