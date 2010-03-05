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


if( empty($digQuery->_fields['search-query']) )
{
    $didumeanQuery = null;
}
else
{
    $didumeanQuery = new digQuery();
    $didumeanArgs = array(
        'dataview' => 'tag_alias',
        'search'   => $digQuery->_fields['search-query'],
    );
    $didumeanQuery->ProcessAdminArgs($didumeanArgs);
    $didumeanQuery->Query();
    $didumeanQuery->_page_opts = array( 'results_func' => 'didUMean_results' );
}

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
					<div class="search-input-container"><input type="text" name="search-query" value="" id="search-query" /></div>
					<div class="search-select-container">
						<select name="search-license" id="search-license" size="1">
							<option value="">All licenses</option>
					        <option value="open">Free for commercial use</option>
						</select>
						<select name="search-type" id="search-type" size="1">
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
						<input name="advanced-search-query" id="advanced-search-query" class="round" />
					</div>
					<div class="advanced-search-row">
						<label for="advanced-search-results" id="advanced-search-results-label">results</label>
						<select id="advanced-search-results">
							<option value="10">10</option>
							<option value="15">15</option>
							<option value="25">25</option>
							<option value="50">50</option>
						</select>
						<label for="advanced-search-since" id="advanced-search-since-label">since</label>
						<select id="advanced-search-since" name="advanced-search-since">
							<option value="">Forever</option>
							<option value="1 days ago">Yesterday</option>
							<option value="1 weeks ago">1 week ago</option>
							<option value="2 weeks ago">2 weeks ago</option>
							<option value="1 months ago">Last month</option>
							<option value="3 months ago">3 months ago</option>
							<option value="1 years ago">1 year ago</option>
						</select>
						<label for="advanced-search-sortby" id="advanced-search-sortby-label">sort</label>
						<select name="advanced-search-sortby" id="advanced-search-sortby">
							<option value="rank">Popularity</option>
							<option value="date">Date</option>
							<option value="name">Track name</option>
							<option value="user">Musician</option>
						</select>
						<select name="advanced-search-sortdir" id="advanced-search-sortdir">
							<option value="desc">Descending</option>
							<option value="asc">Ascending</option>
						</select>
						
						<!-- label for="advanced-search-license" id="advanced-search-license-label">license</label>
						<select style="display:none" name="advanced-search-license" id="advanced-search-license" size="1">
							<option value="">All licenses</option>
					        <option value="open">Free for commercial use</option>
						</select -->
                        
						<label for="advanced-search-stype" id="advanced-search-stype-label">combine</label>
						<select name="advanced-search-stype" id="advanced-search-stype" size="1">
							<option value="all">All words</option>
							<option value="any">Any word</option>
							<option value="match">Exact phrase</option>
						</select>
						<input type="hidden" name="advanced-search-tags" value="" id="advanced-search-tags" />
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
					<!-- div class="advanced-search-row advanced-search-row-last">
						<div class="advanced-search-button-container">
							<input id="advanced-search" type="image" alt="Search" src="images/advanced-search-button-bg.png" />
						</div>
						<div class="clearer"></div>
					</div -->
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