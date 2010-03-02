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

$temp = explode('?',$_SERVER['REQUEST_URI']);
if( count($temp) > 1 )
{
    $uri_query_string = $temp[1];
    parse_str($uri_query_string,$uri_query_args);
}
else
{
    $uri_query_string = '';
    $uri_query_args = array();
}

$query_info = array(
    
    'query_args' => $query_args,

    'query_opts' => array (
        'paging' => true,
        'doc'    => $doc_page,
        'calling_args_str' => $uri_query_string,
        'calling_args' => $uri_query_args,
        'mode'   => 'server'   // temp flag during tansition from ajax to server
    ),
);

$query_info['query_args'] = array_merge($query_info['query_args'],$query_info['query_opts']['calling_args']);
perform_query($query_info);

if( !empty($query_info['results']) )
{    
    $json_params = CCZend_Json_Encoder::encode($query_info['query_opts']['calling_args']);
    $json_opts   = CCZend_Json_Encoder::encode($query_info['query_opts']);
    
    $script_for_head =<<<EOF
        <script type="text/javascript">
        queryObj = new ccmQuery({$json_opts},{$json_params},null);
        queryObj.values = {
                total: {$query_info['total']},
                limit: {$query_info['limit']},
                offset: {$query_info['offset']}
            };
        
        jQuery(document).ready(function() {
            {$results_func}({$query_info['json']});
        });
        </script>
EOF;
}

require_once('lib/head.php');
?>
	<div id="content">
		<div class="page full" id="<?= $page_div_id ?>">
			<h2><?= $h2_title ?></h2>
			<div id="results">
			</div>
			<div class="clearer"></div>
            <noscript>
            <? no_script_results( $query_info ); ?>
            </noscript>
		</div>
        <? require_once('lib/footer.php'); ?>
	</div>
  </div>
</body>
</html>