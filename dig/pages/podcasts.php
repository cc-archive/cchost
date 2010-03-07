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

// ------ PODCASTS ----------

$query_args = array(
    'dataview' => 'topics_podinfo',
    'type'     => 'podcast',
    'datasource' => 'topics',
    'limit'    => 10,
    'offset'   => 1
);

$queries['podcasts'] = new digQuery(DIG_PAGING_ON);
$queries['podcasts']->ProcessUriArgs();
$queries['podcasts']->ProcessAdminArgs($query_args);
$queries['podcasts']->Query();

$queries['podcasts']->_page_opts['results_func'] = 'podcastPageQueryResults';


$script_heads[] = queries_to_jscript( $queries );

$page_opts = CCZend_Json_Encoder::encode($queries['podcasts']->_page_opts);

$script_heads[] =<<<EOF
    <script type="text/javascript">
        page_opts = {$page_opts};
    </script>
EOF;


$page_title = 'dig.ccmixter Podcasts';
$featured_class = 'class="current"';
            
require_once('lib/head.php');
?>
	<div id="content">
		<div class="page full">
			<h2>ccMixter Podcasts</h2>
			<div id="podcasts">
				<!-- div id="debug"></div -->
				<div id="results">
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