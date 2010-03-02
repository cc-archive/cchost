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

require_once('config.php');

if( !defined('CC_HOST_CMD_LINE') )
    define('CC_HOST_CMD_LINE', 1 );      // define this exact way
    

// should be in some kind of common.inc:
require_once( $MIXTER_ROOT_DIR . '/cchost_lib/zend/json-encoder.php' );

/**
 * do the actual query with ccHost
 */
function _query(&$qi)
{
    global $MIXTER_ROOT_DIR;
    
    chdir( $MIXTER_ROOT_DIR );           // must be run from the cchost install root dir
    $NO_EXTRANEOUS_OUTPUT = 1;           // supress tagline of this file (OPTIONAL)
    require_once( 'cc-cmd-line.inc' );  
    require_once( 'cchost_lib/cc-query.php');

    if( !empty($qi['query_opts']['paging'] ) )
    {
        $query = new CCQuery();
        $args = $qi['query_args'];    
        $args['format'] = 'count';
        $args = $query->ProcessAdminArgs($args);
        list( $count ) = $query->Query($args);
        $qi['total'] = trim($count,'[]');
    }
    
    $query = new CCQuery();
    $args = $qi['query_args'];
    $args['format'] = 'php';
    $args = $query->ProcessAdminArgs($args);
    list( $results, $mime ) = $query->Query($args);

    // dbg($query);

    chdir( dirname(__FILE__) . '/..' );

    $qi['results'] = & $results;
    $qi['queryObj'] = & $query;

}

function perform_query(&$qi)
{
    _query($qi);

    //dbg($qi);

    $args = $qi['queryObj']->args;
    if( !empty($qi['query_opts']['paging'] ) )
    {
        $qi['limit']  = $args['limit'];
        $qi['offset'] = $args['offset'];
    }

    $results = & $qi['results'];
    
    if( !empty($results[0]['files']) )
    {
        $keys = array_keys($qi['results']);
        foreach( $keys as $K )
        {
            $R = & $qi['results'][$K];
            $R['num_files'] = count($R['files']);
        }
    }
    
    $qi['json'] = CCZend_Json_Encoder::encode($results);
    
}

function dbg(&$obj)
{
    $str =& _textize($obj);
    $html = '<pre style="font-size: 10pt;text-align:left;">' .
            htmlspecialchars($str) .
            '</pre>';
    print("<html><body>$html</body></html>");
    exit;
}

function & _textize(&$var)
{
    ob_start();
    if( is_array($var) || is_object($var) || is_resource($var) )
        print_r($var);
    else
        var_dump($var);
    $t = ob_get_contents();
    ob_end_clean();

    $r =& $t;
    return($r);
}

function no_script_results( &$qi )
{
    global $DIG_ROOT_URL ;

    print '<ul>';
    $recs = & $qi['results'];
    $keys = array_keys($recs);
    foreach( $keys as $K )
    {
        $R =& $recs[$K];
        if( !empty($R['file_page_url']) )
        {
            print "<li><a href='{$R['file_page_url']}'>" .
                  "{$R['upload_name']}</a> by " .
                  "<a href='{$R['artist_page_url']}'>{$R['user_real_name']}</a> {$R['upload_tags']}</li>\n";
        }
        elseif( !empty($R['enclosure_url']) )
        {
            print "<li><a href='{$R['enclosure_url']}'>{$R['topic_name']}</a> by {$R['user_real_name']}</li>\n";
        }
    }
    print '</ul>';
    if( !empty($qi['query_opts']['paging']) )
    {
        $base = $DIG_ROOT_URL  . $qi['query_opts']['doc'] . '?ns=1';
        $keys = array_diff( array_keys($qi['query_opts']['calling_args']), array('offset','limit') );
        $args = $qi['query_opts']['calling_args'];
        foreach( $keys as $K )
        {
            $base .= '&' . $K . '=' . $args[$K];
        }
        print '<ul>';
        $page = 1;
        for( $i = 0; $i < $qi['total']; $i += $qi['limit'], $page++ )
        {
            print "<li><a href='{$base}&offset={$i}'>page: {$page}</a></li>\n";
        }
        print '</ul>';
    }
}

?>