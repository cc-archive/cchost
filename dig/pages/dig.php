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
    $page_title = 'dig.ccmixter Dig Results';
    $dig_class = 'class="current"';
                
    require_once('lib/head.php');
    
?>
<script type="text/javascript" charset="utf-8">
function execute_url()
{
  <?
  
		if( !empty($_GET) )
		{
            $fields = array();
            $params = array();
            foreach( $_GET as $K => $V )
            {
                if( preg_match('/^(advanced-)?search-/',$K) )
                {
                    $fields[] = "\$('#{$K}').val('{$V}');";
                }
                else
                {
                    if( !in_array($K,array('page','x','y')) )
                    {
                        $params[] = "parameters.{$K} = '{$V}';";
                    }
                }
            }
            if( empty($params) )
                $params = '';
            else
                $params = join("    \n",$params);
                
            if( empty($fields) )
                $fields = '';
            else
                $fields = join("    \n",$fields);
            
            if( !empty($params) || !empty($fields) )
            {
                $js =<<<EOF
    var parameters = {}; 
    {$params}  
    update_fields(parameters);
    {$fields}
    do_search();
    
EOF;
                print $js;
            }
		}
  ?>
  
}
</script>

	<div id="content">
		<div class="page full" id="dig">
			<!-- <h2>dig</h2> -->
			<div class="search-utility round">
				<form action="#">
					<div class="search-input-container"><input type="text" name="search-query" value="" id="search-query" /></div>
					<div class="search-select-container">
						<select name="search-license" id="search-license" size="1">
							<option value="">All licenses</option>
					        <option value="open">Free for commercial use</option>
						</select>
						<select name="search-type" id="search-type" size="1">
							<option value="">All types</option>
					        <option value="videos">For video use</option>
							<option value="games">For game use</option>
							<option value="podcasting">For podcasts</option>
							<option value="entertainment">Entertain me!</option>
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
							<option value="*">Forever</option>
							<option value="1 days ago">Yesterday</option>
							<option value="1 weeks ago">1 week ago</option>
							<option value="2 weeks ago">2 weeks ago</option>
							<option value="1 months ago">Last month</option>
							<option value="3 months ago">3 months ago</option>
							<option value="1 years ago">1 year ago</option>
						</select>
						<label for="advanced-search-sortby" id="advanced-search-sortby-label">sort</label>
						<select name="advanced-search-sortby" id="advanced-search-sortby">
							<option value="popularity">Popularity</option>
							<option value="date">Date</option>
							<option value="track-name">Track name</option>
							<option value="musician">Musician</option>
						</select>
						<select name="advanced-search-sortdir" id="advanced-search-sortdir">
							<option value="desc">Descending</option>
							<option value="asc">Ascending</option>
						</select>
						
						<label for="advanced-search-license" id="advanced-search-license-label">license</label>
						<select name="advanced-search-license" id="advanced-search-license" size="1">
							<option value="">All licenses</option>
					        <option value="open">Free for commercial use</option>
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
					<div class="advanced-search-row">
						<label id="tags-label">tags</label>
						<div id="tags-container"></div>
						<div class="clear-button-container"><a href="#" id="clear">Clear</a></div>
						<div class="clearer"></div>
					</div>
					<div class="advanced-search-row advanced-search-row-last">
						<div class="advanced-search-button-container">
							<input id="advanced-search" type="image" alt="Search" src="images/advanced-search-button-bg.png" />
						</div>
						<div class="clearer"></div>
					</div>
					</form>
			</div>
			<div class="advanced"><a href="#" class="advanced-search-link">Advanced dig</a><a href="#" class="basic-search-link">Basic dig</a></div>
            <?
                if( !empty($_GET['title']) )
                {
                    print '<h1 id="results-title">' . $_GET['title'] . '</h1>';
                }
            ?>
			<div id="didumean"></div>
			<div id="results">
			</div>
			<div class="clearer"></div>
		</div>
        <?
            require_once('lib/footer.php');
        ?>
	</div>
  </div>
</body>
</html>