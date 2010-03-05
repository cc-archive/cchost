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
require_once('lib/util.php');

if( !defined('CC_HOST_CMD_LINE') )
    define('CC_HOST_CMD_LINE', 1 );      // define this exact way
    
chdir( $MIXTER_ROOT_DIR );           // must be run from the cchost install root dir
$NO_EXTRANEOUS_OUTPUT = 1;           // supress tagline of this file (OPTIONAL)
//$admin_id = 9;
require_once( 'cc-cmd-line.inc' );  
require_once( 'cchost_lib/cc-query.php');
chdir( dirname(__FILE__) . '/..' );

define('DIG_PAGING_ON', true);
define('DIG_PAGING_OFF', false);

class digQuery
{
    function digQuery($paging=DIG_PAGING_OFF)
    {
        $this->_clear();
        $this->_control['paging'] = $paging;
    }
  
    function Query()
    {
        global $MIXTER_ROOT_DIR;
        
        $cwd = getcwd();           // there are many on-the-fly includes...
        chdir($MIXTER_ROOT_DIR);   // ...that assume cchost root dir
        
        if( !empty($this->_control['paging']) )
        {
            $query = new CCQuery();
            $args = $this->_query_args; // copy
            $args['format'] = 'count';
            $args = $query->ProcessAdminArgs($args);
            list( $count ) = $query->Query($args);
            $this->total = trim($count,'[]');
        }
        
        $this->queryObj = new CCQuery();
        $A = $this->queryObj->ProcessAdminArgs($this->_query_args);
        $A['format'] = 'php';
        $this->queryObj->Query($A);
        $this->results =& $this->queryObj->records;

        chdir($cwd);

        if( !empty($this->_control['paging']) )
        {
            $this->limit  =& $this->queryObj->args['limit'];
            $this->offset =& $this->queryObj->args['offset'];
        }        
        if( !empty($this->results[0]['files']) )
        {
            $keys = array_keys($this->results);
            foreach( $keys as $K )
            {
                $F = & $this->results[$K];
                
                // dig js wants this fields
                $F['num_files'] = count($F['files']);
                
                // remove these to reduces size and not encourage
                // nosy assholes
                
                $keys = array_keys($F['files']);
                foreach( $keys as $K )
                {
                    $file =& $F['files'][$K];
                    foreach( array('local_path','file_is_remote', 'file_num_download') as $K2 )
                    {
                        if( isset($file[$K2]) )
                            unset($file[$K2]);
                    }
                }
                
                foreach( array('howididit','edpicks','usertags','ccud','systags','relative_dir') as $K )
                {
                    if( !empty($F['upload_extra'][$K]) )
                        unset($F['upload_extra'][$K]);
                }
            }
        }
    
    }
    /**
     *  Parse and translate request args
     *  into query args.
     *
     *  There are two sets of incoming request args:
     *
     *  - 'pretty' version that is used in the browser.
     *  - the actual args as translated by ReWrite rules
     *
     *  These need be setup for use in the following
     *  contexts:
     *  
     *  - query args passed to ccM
     *  - query count args passed to ccM
     *  - pagination URLs
     *  - converted to json for use on client side
     *
     *  
     */
    function ProcessUriArgs()
    {
        global $DIG_ROOT_URL;
        
        // these were as passed in the browser addr bar
        if( preg_match('%/([^?]+)(\?([^#]+))?%',strip_slash($_SERVER['REQUEST_URI']),$m)  )
        {
            $this->_control['doc_url'] = $m[1];

            $this->_page_opts['post_back_url'] = $DIG_ROOT_URL . '/' . $m[1];
            
            $paging = !empty($this->_control['paging']);
            
            
            if( $paging )
            {
                $this->_page_opts['pagination_url'] = $this->_page_opts['post_back_url']  . '?';
            }

            if( !empty($m[3]) )
            {
                $this->pretty_args = new digArgs($m[3],array('offset'));
                if( $paging && !empty($this->pretty_args->stripped_query_str) )
                {
                    $this->_page_opts['pagination_url'] .= $this->pretty_args->stripped_query_str . '&';
                }
            }
        }

        // after translation by mod_rewrite:
        $this->raw_args = new digArgs(strip_slash($_GET), array('offset')); // SERVER['QUERY_STRING'],array('offset'));
        
        // browser args take precedence
        if( empty($this->pretty_args) )
        {
            $all = $this->raw_args->args;
        }
        else
        {
            $all = array_merge($this->raw_args->args,$this->pretty_args->args);
        }
        
        // seperate out the html page fields
        $this->_fields = array();
        
        foreach( $all as $K => $V )
        {
            if( preg_match('/^(search|advanced)-/i',$K) )
            {
                $this->_fields[$K] = $V;
            }
        }

        $all = array_diff_assoc($all,$this->_fields);
        
        // separate out dig control fields
        
        foreach( array('page,dquery') as $K )
        {
            if( !empty($all[$K]) )
            {
                $this->_control[$K] = $all[$K];
            }
        
        }
        
        foreach( array('results_func,adv') as $K )
        {
            if( !empty($all[$K]) )
            {
                $this->_page_opts[$K] = $all[$K];
            }
        
        }
 
        // what's left should be a request version
        // the query args (right?)

        $clean_all = array_diff_assoc($all,$this->_control,$this->_page_opts);

        $this->req_query_args = new digArgs($clean_all);

    }
    
    /**
     *  Combine admin args with any URL args
     *  (if any). Flatten all tag arguments
     *  into a tagexp
     */     
    function ProcessAdminArgs($args)
    {
        $this->admin_args = new digArgs($args);
 
 
        // we're not making _query_args an object
        // because we already have all the constituent
        // parts to calculate things out
        
        if( empty($this->req_query_args) )
        {
            $this->_query_args = $args;            
        }
        else
        {
            $this->_query_args = array_merge($args,$this->req_query_args->args);
        }
 
       
        // we have every arg and tag, now fixup tags so that all
        // the various arg contributors get combined into one
        // tagexpr 
        
        $exps = array();
          
        //  
        // order specific: precedence goes left (low) to right (high)
        //
        foreach( array('raw_args','pretty_args','admin_args') as $aobj )
        {
            if( empty($this->$aobj) )
                continue;
            
            $O =& $this->$aobj;
            
            // if this already has a tagexp, just use it
            
            if( !empty($O->args['tagexp']) )
            {
                $exps[] = $O->args['tagexp'];
                continue;
            }
            
            // reqtags are a natural
            
            if( !empty($O->regtags) )
            {
                $exps[] = join('*',$O->reqtags);
            }
            
            // if there's no tags then we're done
            
            if( empty($O->tags) )
            {
                continue;
            }

            // now add the tags
            
            $jchar = '*';
            
            if( !empty($O->args['type']) )
            {
                if( $O->args['type'] == 'any' )
                    $jchar = '|';
            }
            
            $exps[] = join($jchar,$O->tags);
        }
        
        if( !empty($exps) )
        {
            // yea, there might be dupes
            
            $exps = array_unique($exps);
            
            $this->_query_args['tagexp'] = '(' . join(')*(',$exps) . ')';
            
            // don't confuse the query engine and just
            // nuke out the tags argument
            
            if( !empty($this->_query_args['tags']) )
            {
                unset($this->_query_args['tags']);
            }
        }
        
    }
    
    function _clear()
    {
        $this->_fields         = array();
        $this->_control        = array();
        $this->_query_args     = array();
        $this->_page_opts      = array( 'mode' => 'server' );
        
        $this->queryObj = null;
        
        $this->admin_args     = null;
        $this->pretty_args    = null;
        $this->raw_args       = null;
        $this->req_query_args = null;
    }
}

function queries_to_jscript($queries)
{    
    if( empty($queries) )
        return '';
    
    $qs = '';
    $inits = '';
    foreach( $queries as $Q )
    {
        if( $Q === null )
            continue;
        
        $json_params  = empty($Q->pretty_args->args) ? '{}' : CCZend_Json_Encoder::encode($Q->pretty_args->args);
        $json_opts    = empty($Q->_page_opts) ? '{}' : CCZend_Json_Encoder::encode($Q->_page_opts);
        $json_results = CCZend_Json_Encoder::encode($Q->results);
        
        $json_results = str_replace('{"upload_id"', "\n" . '{"upload_id"', $json_results);
        $json_results = str_replace('"upload_description_plain"', "\n    " . '"upload_description_plain"', $json_results );
        $json_results = str_replace('"howididit"', "\n    " . '"howididit"', $json_results );
        
        $qs .= "queryObj = new ccmQuery({$json_opts},{$json_params},null);\n";
        
        
        if( !empty($Q->_page_opts['paging']) )
        {            
            $qs .= <<<EOF
    queryObj.values = { 
            total: {$Q->total},
            limit: {$Q->limit},
            offset: {$Q->offset}
                };

EOF;
        }
        
        $inits .= "{$Q->_page_opts['results_func']}({$json_results});\n";
        
        if( !empty($Q->_fields) )
        {
            foreach( $Q->_fields as $K => $F )
            {
                $inits .= "\t\t\t\tF = \$('#' + '{$K}'); if( F ) { F.val('{$F}'); }\n";
            }
        }
    }
    

    $js =<<<EOF
        <script type="text/javascript">
            {$qs}
        
            jQuery(document).ready(function() {
                var F;
                {$inits}
            });
        
        </script>
EOF;

    return $js;
}

function queries_to_no_script($queries)
{
    foreach( $queries as $qi )
    {
        if( $qi === null )
            continue;
        
        print '<noscript>';

        print '<ul>';
            $recs = & $qi->results;
            $keys = array_keys($recs);
            foreach( $keys as $K )
            {
                $R =& $recs[$K];
                if( !empty($R['file_page_url']) )
                {
                    $tags = str_replace(',',', ',$R['upload_tags']);
                    print "<li><a href='{$R['file_page_url']}'>" .
                          "{$R['upload_name']}</a> by " .
                          "<a href='{$R['artist_page_url']}'>{$R['user_real_name']}</a> {$tags}</li>\n";
                }
                elseif( !empty($R['enclosure_url']) )
                {
                    print "<li><a href='{$R['enclosure_url']}'>{$R['topic_name']}</a> by {$R['user_real_name']}</li>\n";
                }
            }
        print '</ul>';
        
        if( !empty($qi->_page_opts['paging']) )
        {
            $base = $qi->_page_opts['pagination_url'];
            if( $qi->offset > 0 )
            {
                if( ($offset = $qi->offset - $qi->limit) < 0 )
                    $offset = 0;
                    
                print "<p><a href='{$base}offset={$offset}'>prev</a></p>\n";
            }
            if( ($offset = $qi->offset + $qi->limit) < $qi->total )
            {
                print "<p><a href='{$base}offset={$offset}'>next</a></p>\n";
            }
        }
        
        print '</noscript>';
    }
}

?>