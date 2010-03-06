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

require_once('lib/util.php');
require_once('lib/dig_util.php');
require_once('lib/query.php');

$digQuery = new digQuery(DIG_PAGING_ON);
$digQuery->_page_opts['paging'] = true;
$digQuery->_page_opts['parent'] = '#search-utility';
$digQuery->_page_opts['results_func'] = 'query_results';
$digQuery->ProcessUriArgs();
$digQuery->ProcessAdminArgs(prep_dig_query_args($digQuery));
$digQuery->Query();


if( empty($digQuery->_fields['dig-query']) )
{
    $didumeanQuery = null;
}
else
{
    $didumeanQuery = new digQuery();
    $didumeanArgs = array(
        'dataview' => 'tag_alias',
        'search'   => $digQuery->_fields['dig-query'],
    );
    $didumeanQuery->ProcessAdminArgs($didumeanArgs);
    $didumeanQuery->Query();
    $didumeanQuery->_page_opts = array( 'results_func' => 'didUMean_results' );
}

// ----------


$script_heads[] = '<script type="text/javascript" src="js/plugins/jquery.toChecklist.js"></script>' . "\n" ; /*
                  '<link rel="stylesheet" href="css/plugins/jquery.multiSelect.css" type="text/css" />' . "\n"; */

// ----------
$queries = array( &$didumeanQuery, &$digQuery ) ;
if( !empty($_REQUEST['dquery'] ) )
{
    $digQuery->query_str = http_build_query($digQuery->_query_args);
    dbg($queries);
}
$script_heads[] = queries_to_jscript( $queries );
if( !empty($digQuery->pretty_args->args['adv']) )
{
    $script_heads[] = '<script type="text/javascript"> adv_showing = true; </script>' . "\n";
}
$page_title = empty($digQuery->_query_args['title']) ? 'dig.ccmixter' : $digQuery->_query_args['title'];
$dig_class = 'class="current"';

require_once('lib/head.php');
    
?>

	<div id="content">
		<div class="page full" id="dig">
			<!-- <h2>dig</h2> -->
			<div class="search-utility round">
				<form action="#" >
					<div class="search-input-container"><input type="text" name="dig-query" value="" id="dig-query" /></div>
					<div class="search-select-container">
						<select name="dig-lic" id="dig-lic" size="1">
							<option value="">All licenses</option>
					        <option value="open">Free for commercial use</option>
						</select>
						<select name="dig-type" id="dig-type" size="1">
							<option value="dig">All types</option>
					        <option value="music_for_film_and_video">For video use</option>
							<option value="music_for_games">For game use</option>
							<option value="podcast_music">For podcasts</option>
							<option value="cubicle_music">Cubicle music</option>
							<option value="party_music">Party music</option>
						</select>
					</div>
					
					<div class="search-button-container">
						<input id="search" type="image" alt="Search" src="images/search-button-bg.png" />
					</div>
				</form>
				<div id="debug">&nbsp;</div>
			</div>
			<div class="advanced-search-utility round" >
					<form action="#">
					<div class="advanced-search-row">
						<input name="advanced-dig-query" id="advanced-dig-query" class="round" />
					</div>
					<div class="advanced-search-row">
						<label for="dig-limit" id="dig-limit-label">results</label>
						<select id="dig-limit">
							<option value="10">10</option>
							<option value="15">15</option>
							<option value="25">25</option>
							<option value="50">50</option>
						</select>
						<label for="dig-since" id="dig-since-label">since</label>
						<select id="dig-since" name="dig-since">
							<option value="">Forever</option>
							<option value="1 days ago">Yesterday</option>
							<option value="1 weeks ago">1 week ago</option>
							<option value="2 weeks ago">2 weeks ago</option>
							<option value="1 months ago">Last month</option>
							<option value="3 months ago">3 months ago</option>
							<option value="1 years ago">1 year ago</option>
						</select>
						<label for="dig-sort" id="dig-sort-label">sort</label>
						<select name="dig-sort" id="dig-sort">
							<option value="rank">Popularity</option>
							<option value="date">Date</option>
							<option value="name">Track name</option>
							<option value="user">Musician</option>
						</select>
						<select name="dig-ord" id="dig-ord">
							<option value="desc">Descending</option>
							<option value="asc">Ascending</option>
						</select>
						
						<label for="dig-stype" id="dig-stype-label">combine</label>
						<select name="dig-stype" id="dig-stype" size="1">
							<option value="all">All words</option>
							<option value="any">Any word</option>
							<option value="match">Exact phrase</option>
						</select>
						<input type="hidden" name="dig-tags" value="" id="dig-tags" />
					</div>
					<div id="tagpicker" class="advanced-search-row">
						<div class="tag-select-container">
							<label>genre</label>
							<div id="genre_results" class="tag-select  round">getting data&hellip;</div>
						</div>
						<div class="tag-select-container">
							<label>instrument</label>
							<div id="instr_results" class="tag-select round">getting data&hellip;</div>
						</div>
						<div class="tag-select-container tag-select-container-last">
							<label>style</label>
							<div id="mood_results" class="tag-select round">getting data&hellip;</div>
						</div>
						<div class="clearer"></div>
					</div>
					<div class="advanced-search-row  advanced-search-row-last">
						<label id="tags-label">tags</label>
						<div id="tags-container"></div>
						<div class="clear-button-container"><a href="#" id="clear">Clear</a></div>
						<div class="clearer"></div>
					</div>
					</form>
			</div>
			<div class="advanced"><a href="#" class="advanced-search-link">Advanced dig</a><a href="#" class="basic-search-link">Basic dig</a></div>
            <?
                if( !empty($page_title) )
                {
                    print "<h2>{$page_title}</h2>";
                }
            ?>
			<div id="didumean"></div>
			<div id="results">
			</div>
			<div class="clearer"></div>
            <? queries_to_no_script(array($digQuery)); ?>
		</div>
        <?
            require_once('lib/footer.php');
        ?>
	</div>
  </div>
</body>
</html>