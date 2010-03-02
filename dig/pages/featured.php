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

$sections = array( 
    array( // ed picks
        
        'query_args' => array(
            'dataview' => 'diginfo',
            'tags'     => 'editorial_pick',
            'limit'    => 6
        ),
    
        'query_opts' => array (
            'doc'    => 'featured',
            'func'   => 'edpickQueryResults',
            'mode'   => 'server'   // temp flag during tansition from ajax to server
        ),
    ),
    
    array( // popular
        
        'query_args' => array(
            'dataview' => 'diginfo',
            'tags'     => 'remix',
            'sort'     => 'rank',
            'sinced'   => '2 weeks ago',
            'limit'    => 6
        ),
    
        'query_opts' => array (
            'doc'    => 'featured',
            'func'   => 'popchartQueryResults',
            'mode'   => 'server'   // temp flag during tansition from ajax to server
        ),
    ),


    array( // podcasts
        
        'query_args' => array(
            'dataview' => 'topics_podinfo',
            'type'     => 'podcast',
            'limit'    => 10,
            'offset'   => 1
        ),
    
        'query_opts' => array (
            'doc'    => 'featured',
            'func'   => 'podcastQueryResults',
            'mode'   => 'server'   // temp flag during tansition from ajax to server
        ),
    )
);


$script_for_head =  "<script type=\"text/javascript\">\n" .
                    "    jQuery(document).ready(function() {\n";

foreach( $sections as $S )
{
    perform_query($S);
    $json = CCZend_json_Encoder::encode($S['query_opts']);
    $script_for_head .=<<<EOF
        queryObj = new ccmQuery({$json},{},null);
        {$S['query_opts']['func']}({$S['json']});

EOF;
}

$script_for_head .= "    });\n" .
                    "    </script>\n";


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
                <noscript>
<?
    foreach( $sections as $S ) {
        no_script_results($S);
        print "<a href=\"{$DIG_ROOT_URL}/{$S['query_opts']['doc']}\">more</a><br />\n";
    }
?>
                </noscript>
            </div>
		</div>
	<? require_once('lib/footer.php'); ?>
	</div>
  </div>
</body>
</html>