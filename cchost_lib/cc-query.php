<?
/*
* Creative Commons has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file LICENSE.TXT.
* 
* You may use the ccHost software in accordance with the
* terms of that license. You agree that you are solely 
* responsible for your use of the ccHost software and you
* represent and warrant to Creative Commons that your use
* of the ccHost software will comply with the CC-GNU-GPL.
*
* $Id$
*
*/

/**
* Query API
*
* The request is handled in 3 main phases:
*
*   PHASE 1
*     Interpret the parameters to determine the data source and data view (see _validate_sources)
*   
*   
*   - Perform the SQL query
*   - Format the output and return it to caller
*
* 
*
* @package cchost
* @subpackage api
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cchost_lib/cc-tags.php');

/**
*
*
*/
class CCQuery 
{
    function CCQuery()
    {
        $this->sql = '';
        $this->sql_p = array(   
                        'columns' => '',
                        'joins' => array(),
                        'where' => '',
                        'order' => '',
                        'limit' => '', 
                        'group_by' => '' );
        $this->where = array();
        $this->args = array();
        $this->records = array();
    }

    /**
    * Entry point for api/query
    *
    */
    function QueryURL()
    {
        $this->ProcessUriArgs();

        list( $value, $mime ) = $this->Query(); // This method MAY exit the session... 

        if( $value === true ) // handled elsewhere 
            return;           // return and show the page

        if( empty($value) ) 
            CCUtil::Send404(true);  // We didn't find anything, slap back a 404

        if( !empty($mime) )
            header( "Content-type: $mime" );

        print($value);
        exit;
    }

    /**
    * Use this when calling from php (call ProcessAdminArgs first to clean the args)
    *
    */
    function Query($args=array())
    {
        if( !empty($args) )
            $this->args = $args;

        if( !empty($this->_sql_squeeze) )
           $this->sql_p = array_merge($this->sql_p,$this->_sql_squeeze);

        $this->_validate_sources();

        if( $this->args['datasource'] == 'uploads' )
            $this->_gen_visible();

        if( empty($this->args['cache']) )
        {
            $this->_generate_records();
        }
        else
        {
            $this->_generate_records_from_cache();
        }

        //
        // Process the resulting records here
        //
        if( $this->args['format'] == 'count' )
        {
            return( array( '[' . $this->records . ']', 'text/plain' ) );
        }
        elseif( $this->args['format'] == 'ids' )
        {
            $text = empty($this->records) ? '-' : join(';',$this->records );

            return( array( $text, 'text/plain' ) );
        }

        // Do NOT return at this point if records are empty, 
        // an empty feed is still valid

        if( !empty($this->records) && !empty($this->args['nosort']) && !empty($this->args['ids']) )
        {
            $ids = is_string($this->args['ids']) ? $this->_split_ids($this->args['ids']) : $this->args['ids'];
            $i = 0;
            foreach($ids as $id)
                $sort_order[$id] = $i++;
            $this->_resort_records($this->records,$sort_order,'upload_id');
        }

        switch( $this->args['format'] )
        {
            case 'undefined':
            case 'php':
                break;

            case 'phps':
                return array( serialize($this->records), 'text/plain' );

            default:
            {
                $results = '';
                $results_mime = '';
                $this->args['queryObj'] = $this;

                CCEvents::Invoke( CC_EVENT_API_QUERY_FORMAT, 
                                    array( &$this->records, &$this->args, &$results, &$results_mime ) );
                return array( $results, $results_mime );
            }
        } // end switch

        return array( &$this->records, '' );
    }

    /**
    * Call this from php when you need to hack in some SQL (call ProcessAdminArgs first on $qargs)
    *
    */
    function QuerySQL($qargs,$sqlargs)
    {
        $this->_sql_squeeze =  $sqlargs;
        return $this->Query($qargs);
    }

    /**
    * Call this to fetch and clean args that were passed in through the browser
    */
    function ProcessUriArgs($extra_args = array())
    {
        global $CC_GLOBALS;

        $this->_from_url = true;

        $req = !empty($_POST) ? $_POST : $_GET;

        if( empty($req) )
            return $extra_args;

        if( !empty($req['ccm']) )
            unset($req['ccm']);

        CCUtil::Strip($req);

        $this->_arg_alias_ref($req); // convert short to long
        $this->_uri_args = $req;     // store for later

        $this->args = array_merge($this->GetDefaultArgs(),$req,$extra_args);

        // get the '+' out of the tag str
        if( !empty($this->args['tags']) )
            $this->args['tags'] = str_replace( ' ', ',', urldecode($this->args['tags']));

        // queries might need decoding
        if( !empty($this->args['search']) )
            $this->args['search'] = urldecode($this->args['search']);

        $k = array_keys($this->args);
        $n = count($k);
        for( $i = 0; $i < $n; $i++)
        {
            $tt = $this->args[$k[$i]];
            if( is_string($tt) && (strpos($tt,'\'') === (strlen($tt)-1) ) )
                die('Illegal value in query');
        }

        $this->_validate_sources();
        $this->_validate_args();

        return $this->args;
    }

    /** 
    * Helper function for formats during CC_EVENT_QUERY_SETUP
    *
    */
    function GetSourcesFromDataview($dataview_name)
    {
        if( empty($this->dataview) )
        {
            require_once('cchost_lib/cc-dataview.php');
            $this->dataview = new CCDataView();
        }
        $this->dataviewProps = $this->dataview->GetDataview($dataview_name);
        $this->args['dataview'] = $dataview_name;
        $this->args['datasource'] = empty($this->dataviewProps['datasource']) ? 'uploads' : $this->dataviewProps['datasource'];
    }

    /** 
    * Helper function for formats during CC_EVENT_QUERY_SETUP
    *
    */
    function GetSourcesFromTemplate($template_name)
    {
        if( empty($this->dataview) )
        {
            require_once('cchost_lib/cc-dataview.php');
            $this->dataview = new CCDataView();
        }

        require_once('cchost_lib/cc-template.php');
        $template = $this->template = new CCSkinMacro($template_name);
        $this->templateProps = $template->GetProps();
        $this->dataviewProps = $this->dataview->GetDataviewFromTemplate($template);
        if( empty($this->dataviewProps) )
        {
            $this->templateProps['dataview'] = 'default';
            $this->dataviewProps = $this->dataview->GetDataview('default');
        }

        $this->args['dataview'] = $this->templateProps['dataview'];
        if( empty($this->templateProps['datasource']) )
        {
            $this->args['datasource'] = empty($this->dataviewProps['datasource']) ? 'uploads' : $this->dataviewProps['datasource'];
        }
        else
        {
            //
            // The datasource is right there in the template
            //
            $this->args['datasource'] = $this->templateProps['datasource'];
        }
    }

    /** 
    * Helper function for formats during CC_EVENT_QUERY_SETUP
    *
    */
    function ValidateLimit($admin_limit_key,$max=12)
    {
        if( !empty($this->_limit_is_valid) )
            return;

        global $CC_GLOBALS;
        if( empty($this->_from_url) )
            $admin_limit_key = 'querylimit';
        $admin_limit = empty($admin_limit_key) ? $max : $CC_GLOBALS[$admin_limit_key];
        if( empty($this->args['limit']) || ( $this->args['limit'] == 'default' ) )
            $caller_limit = 'default';
        else 
            $caller_limit = sprintf('%0d',$this->args['limit']);
        if( empty($caller_limit) || ($caller_limit == 'default') || ($caller_limit > $admin_limit) )
            $caller_limit = $admin_limit;
        $this->args['limit'] = $caller_limit;
        $this->_limit_is_valid = true;
    }

    /** 
    * Query phase 1 processing
    *
    * At the end of this data source and data view MUST have been initialized
    *
    */
    function _validate_sources()
    {
        if( !empty($this->_validated_sources) )
            return;

        $A =& $this->args;

        //
        // This is the default return type from dataview::perform
        //
        $A['rettype'] = CCDV_RET_RECORDS;

        //
        // every query must have a format
        //
        if( empty($A['format']) )
            $A['format'] = 'page';

        switch( $A['format'] )
        {
            case 'php':
            case 'phps':
                if( empty($A['dataview']) )
                    $A['dataview'] = 'default';
                $this->GetSourcesFromDataview($A['dataview']);
                break;
            case 'count':
                $A['rettype'] = CCDV_RET_ITEM;
                $this->GetSourcesFromDataview('count');
                $this->sql_p['limit'] = '';
                break;
            case 'ids':
                $A['rettype'] = CCDV_RET_ITEMS;
                $this->GetSourcesFromDataview('ids');
                break;
            default:
                CCEvents::Invoke( CC_EVENT_API_QUERY_SETUP, array( &$this->args, &$this, !empty($this->_from_url)) );
                break;
        }

        $this->_validated_sources = true;
    }

    /** 
    * Validate args from HTTP request
    *
    */
    function _validate_args()
    {
        if( !empty($this->templateProps['valid_args']) )
        {
            $valid = array_unique(preg_split('/([^a-z_]+)/',$this->templateProps['valid_args'],0,PREG_SPLIT_NO_EMPTY));
            if( !empty($valid) )
            {
                $skip = array('format','template','dataview','datasource','offset','limit','sort','ord','dpreview');
                $diff = array_diff(array_diff(array_keys($this->_uri_args),$skip),$valid);
                if( !empty($diff) )
                {
                    $msg = sprintf(_('Invalid query args: "%s"  Valid args are: "%s" '),join( ', ',$diff),$this->templateProps['valid_args']);
                    print $msg;
                    exit;
                }
            }
        }
        if( !empty($this->templateProps['required_args']) )
        {
            $req = array_unique(preg_split('/([^a-z]+)/',$this->templateProps['required_args'],0,PREG_SPLIT_NO_EMPTY));
            if( !empty($req) )
            {
                $gotcha = array_intersect($req, array_keys($this->args)); // not _url_args!
                if( empty($gotcha) )
                {
                    $msg = sprintf(_('Missing required query args: "%s" '),$this->templateProps['required_args']);
                    print $msg;
                    exit;
                }
            }
        }
        if( !empty($this->templateProps['formats']) )
        {
            $valid = array_unique(preg_split('/([^a-z]+)/',$this->templateProps['formats'],0,PREG_SPLIT_NO_EMPTY));
            if( !empty($valid) && empty($this->args['format']) || !in_array( $this->args['format'], $valid ) )
            {
                $this->args['format'] = $valid[0];
            }
        }
    }

    /**
    * Call this before calling Query or QuerySQL
    */
    function ProcessAdminArgs($args,$extra_args=array())
    {
        if( is_string($args) )
        {
            parse_str($args,$args);
            CCUtil::StripSlash($args);
        }

        // alias short to long
        $this->_arg_alias_ref($args);

        $this->args = array_merge($this->GetDefaultArgs(),$args,$extra_args);

        if( !empty($this->args['tags']) )
        {
            // clean up tags 
            require_once('cchost_lib/cc-tags.php');
            $this->args['tags'] = join(',',CCTag::TagSplit($this->args['tags']));
        }

        return $this->args;
    }

    function SerializeArgs($args=array())
    {
        if( empty($args) )
            $args =& $this->args;
        $keys = array_keys($args);
        $default_args = $this->GetDefaultArgs();
        $str = '';

        // alias short to long
        $this->_arg_alias();

        $badargs = array( 'qstring', 'ccm', 'format', 'template', 'dataview', 'datasource' ); 

        foreach( $keys as $K )
        {
            if(  in_array( $K, $badargs ) || empty($args[$K]) || !is_string($args[$K]) ||
                ( array_key_exists($K,$default_args) && ($args[$K] == $default_args[$K]) ) )
            {
                continue;
            }

            if( !empty($str) )
                $str .= '&';

            $str .= $K . '=' . urlencode($args[$K]);
        }
        return $str;
    }

    function GetDefaultArgs()
    {
        global $CC_GLOBALS;

        return array(
                    'sort' => 'date', 'ord'  => 'DESC', 
                    'limit' => 'default', 'offset' => 0,
                    'format' => 'page',
                    );
    }

    function _generate_records()
    {
        foreach( array( 'sort', 'date', ) as $arg )
        {
            $method = '_gen_' . $arg;
            $this->$method();
        }

        foreach( array( 'search', 'tags', 'type', 'playlist', 'ids', 'user', 'remixes', 'sources', 
                         'remixesof', 'score', 'lic', 'remixmax', 'remixmin', 'reccby',  'upload', 'thread',
                         'reviewee', 'match', 'reqtags','rand', 'recc', 'collab',
                        ) as $arg )
        {
            if( isset($this->args[$arg]) )
            {
                $method = '_gen_' . $arg;
                $this->$method();
            }
        }

        $this->_gen_limit();

        if( !empty($this->reqtags) )
        {
            $tagfield = $this->_make_field('tags');
            $this->where[] = $this->dataview->MakeTagFilter($this->reqtags,'all',$tagfield);
        }

        if( !empty($this->tags) )
        {
            $tagfield = $this->_make_field('tags');
            if( empty($this->args['type']) )
                $this->args['type'] = 'all';
            $this->where[] = $this->dataview->MakeTagFilter($this->tags,$this->args['type'],$tagfield);
        }

        if( !empty($this->sql_p['where']) )
            $this->where[] = $this->sql_p['where'];

        $this->sql_p['where'] = empty($this->where) ? '' : '(' . join( ') AND (', $this->where ) . ')' ;

        if( empty($this->dead) )
        {
            $this->dataviewProps['dataview'] = $this->args['dataview'];
            $this->records =&  $this->dataview->Perform( $this->dataviewProps, $this->sql_p, $this->args['rettype'], $this );
            $this->sql =  $this->dataview->sql;
        }


        // ------------- DUMP RESULTS ---------------------

        if( !empty($this->args['dump_query']) && CCUser::IsAdmin() )
        {
            CCDebug::Enable(true);
            CCDebug::PrintVar($this,false);
        }

        if( !empty($_REQUEST['dump_rec']) && CCUser::IsAdmin() )
        {
            CCDebug::Enable(true);
            CCDebug::PrintVar($this->records[0],false);
        }

    }

    function _generate_records_from_cache()
    {
        $cname = cc_temp_dir() . '/query_cache_' . $this->args['cache'] . '.txt';
        if( file_exists($cname) )
        {
            include($cname);
            $this->records =& $_cache_rows;
            //$this->_generate_records();
        }
        else
        {
            $this->_generate_records();

            $data = serialize($this->records);
            $data = str_replace("'","\\'",$data);
            $text = '<? /* This is a temporary file created by ccHost. It is safe to delete. */ ' .
                     "\n" . '$_cache_rows = unserialize(\'' . $data . '\'); ?>';
            CCUtil::MakeSubDirs(dirname($cname));
            $f = fopen($cname,'w+');
            fwrite($f,$text);
            fclose($f);
            chmod($cname,cc_default_file_perms());
        }
    }

    /********************************
    * Generators
    *********************************/
    function _gen_collab()
    {
        if( ($this->args['datasource'] == 'collabs') || ($this->args['datasource'] == 'collab_users') )
        {
            $field = $this->_make_field('collab');
            $this->where[] = sprintf( "($field = '%0d')", $this->args['collab'] );
        }
        elseif( $this->args['datasource'] == 'uploads' )
        {
            $collab_id = sprintf('%0d',$this->args['collab'] );
            if( !empty($collab_id) )
            {
                $ids = CCDatabase::QueryItems('SELECT collab_upload_upload FROM cc_tbl_collab_uploads WHERE collab_upload_collab='.$collab_id);
                if( empty($ids) )
                {
                    $this->dead = true;
                }
                else
                {
                    $this->where[] = '(upload_id IN (' . join(',',$ids) . '))';
                }
            }
        }
    }

    function _gen_date()
    {
        // Check for date limit

        $since = 0;

        if( !empty($this->args['sinced']) )     // text date
        {
            $since = strtotime($this->args['sinced']);
            if( $since < 1 )
                die('invalid date string');
        }
        elseif( !empty($this->args['sinceu']) ) // unix time
        {
            if( $this->args['sinceu']{0} === '_' )
                $this->args['sinceu'] = substr($this->args['sinceu'],1);
            $since = sprintf('%0d',$this->args['sinceu']);
        }

        if( !empty($since) )
        {
            $after = date( 'Y-m-d H:i', $since );
            $field = $this->_make_field('date');
            $this->where[] = "($field > '$after')";
        }

    }

    function _gen_ids()
    {
        $ids = $this->_split_ids($this->args['ids']);
        if( $ids )
        {
            $field = $this->_make_field('id');
            $this->where[] = "($field IN (" . join(',',$ids) . '))';
        }
    }

    function _split_ids($ids)
    {
        return array_unique(preg_split('/([^0-9]+)/',$ids,0,PREG_SPLIT_NO_EMPTY));
    }

    function _gen_lic()
    {
        $translator = array( 
            'by' => 'attribution',
            'nc' => 'noncommercial', 
            'sa' => 'share-alike'   , 
            'nod' => 'noderives'   , 
            'byncsa' => 'by-nc-sa'   , 
            'byncnd' => 'by-nc-nd'   , 
            'by-nc-sa' => 'by-nc-sa'   , 
            'by-nc-nd' => 'by-nc-nd'   , 
            's' => 'sampling'   , 
            'splus' => 'sampling+',
            'ncsplus' =>   'nc-sampling+',
            'pd' =>  'publicdomain' ,
            );

        if( !array_key_exists( $this->args['lic'], $translator ) )
            die('invalid license argument');

        $license = $translator[$this->args['lic']];
        $field = $this->_make_field('license');
        $this->where[] = "($field = '$license')";
    }

    function _gen_limit()
    {
        $this->ValidateLimit('querylimit');
        
        $A =& $this->args;

        if( !empty($A['offset']) )
            $A['offset'] = sprintf('%0d',$A['offset'] );

        if( empty($A['offset']) || ($A['offset'] <= 0) )
            $A['offset'] = '0';

        $this->sql_p['limit'] = $A['limit'] . ' OFFSET ' . $A['offset'];
    }

    function _gen_match()
    {
        // this only works for specific dataviews (see search_remix_artist.php)
        $this->sql_p['match'] = addslashes(trim($this->args['match']));
    }

    function _gen_playlist()
    {
        if( $this->args['datasource'] == 'uploads' )
        {
            $this->sql_p['joins'][] = 'cc_tbl_cart_items ON cart_item_upload=upload_id';
            $this->where[] = 'cart_item_cart = '.$this->args['playlist']; // err, is this right?
        }
    }

    function _gen_rand()
    {
        $this->sql_p['order'] = 'RAND()';
    }

    function _gen_reccby()
    {
        $user_id = CCDatabase::QueryItem("SELECT user_id FROM cc_tbl_user WHERE user_name= '{$this->args['reccby']}'");
        if( !empty($user_id) && $this->args['datasource'] == 'uploads')
        {
            $this->sql_p['joins'][] = 'cc_tbl_ratings ON ratings_upload=upload_id';
            $this->where[] = 'ratings_user = ' . $user_id;
            if( $this->args['format'] != 'count' )
            {
                $this->sql_p['order'] = 'ratings_id DESC'; // er, ....
            }
        }
    }

    function _gen_remixmax()
    {
        $this->where[] = "(upload_num_remixes <= '{$this->args['remixmax']}')";
    }

    function _gen_remixmin()
    {
        $this->where[] = "(upload_num_remixes >= '{$this->args['remixmin']}')";
    }        

    /*
    * List the remixes of an upload (see also remix filter in cc-filter.php)
    */
    function _gen_remixes()
    {
        $this->_heritage_helper('remixes','tree_child','cc_tbl_tree','tree_parent','upload_id');
    }

    /*
    * List the remixes of a PERSON
    */
    function _gen_remixesof()
    {
        $user_id = CCUser::IDFromName($this->args['remixesof']);
        if( empty($user_id) )
        {
            $this->dead = true;
            return;
        }
        $sql = "SELECT tree_child FROM cc_tbl_tree JOIN cc_tbl_uploads ON tree_parent = upload_id WHERE upload_user = " . $user_id;
        $ids = CCDatabase::QueryItems($sql);
        if( empty($ids) )
        {
            $this->dead = true;
            return;
        }
        $this->where[] = 'upload_id IN (' . join(',',$ids) . ')';
    }

    /*
    * Reviews left FOR a person
    */
    function _gen_reviewee()
    {
        if( $this->args['datasource'] == 'topics' )
        {
            /*
                Assumes this is dataview !!!:
            $this->sql_p['joins'] = ' cc_tbl_uploads ups      ON topic_upload = ups.upload_id ' .
                                    'JOIN cc_tbl_user    reviewee ON ups.upload_user = reviewee.user_id';
            */
            $this->where[] = "(reviewee.user_name = '{$this->args['reviewee']}')";
        }
    }

    function _gen_score()
    {
        $this->args['score'] = sprintf('%0d',$this->args['score']);
        if( $this->args['datasource'] == 'user' )
            $this->where[] = 'user_num_scores >= ' . $this->args['score'];
        elseif( $this->args['datasource'] == 'uploads' )
            $this->where[] = 'upload_num_scores >= ' . $this->args['score'];
    }

    function _search_helper($columns,$term)
    {
        if( $term{0} == '-' )
        {
            $neg = 'NOT';
            $term = substr($term,1);
        }
        else
        {
            $neg = '';
        }
        return "LOWER(CONCAT({$columns})) {$neg} LIKE '%{$term}%'";
    }

    function _search_term_parser($term)
    {
        preg_match_all('/((-?"([^"]+)")|(?<=^|\s)([^"\s]+)(?=\s|$))/',$term,$m);
        $res = array();
        foreach($m[0] as $mx)
        {
            if( $mx{0} == '-' )
            {
                if( strlen($mx) > 1 )
                {
                    if( $mx{1} == '"' )
                        $mx = str_replace('"','',$mx);
                }
                else
                {
                    continue;
                }
            }
            elseif( $mx{0} == '"' )
            {
                $mx = str_replace('"','',$mx);
            }

            $res[] = $mx;
        }
        return $res;
    }

    function _gen_search()
    {
        $search_meta = array();
        CCEvents::Invoke( CC_EVENT_SEARCH_META, array(&$search_meta) );
        $grp = empty($this->args['type']) ? 0 : $this->args['type'];

        foreach( $search_meta as $meta )
        {
            if( (!$grp || ($grp == $meta['group'])) && ($this->args['datasource'] == $meta['datasource']) )
            {
                $search = str_replace("'","\\'",(trim($this->args['search'])));
                $strlow = strtolower($search);
                global $CC_GLOBALS;
                if( empty($CC_GLOBALS['use_text_index']) )
                {
                    $stype = empty($this->args['search_type']) ? 'any' : $this->args['search_type'];
                    switch( $stype )
                    {
                        case 'match':
                        {
                            $this->where[] = $this->_search_helper($meta['match'],$strlow);
                            break;
                        }

                        case 'all':
                        {
                            $terms = $this->_search_term_parser($strlow);
                            foreach( $terms as $term )
                                $this->where[] = $this->_search_helper($meta['match'],$term);
                            break;
                        }

                        case 'any':
                        {
                            $terms = $this->_search_term_parser($strlow);
                            $ors = array();
                            foreach( $terms as $term )
                            {
                                if( $term{0} == '-' )
                                    $this->where[] = $this->_search_helper($meta['match'],$term);
                                else
                                    $ors[] = $this->_search_helper($meta['match'],$term);
                            }
                            if( !empty($ors) )
                                $this->where[] = join( ' OR ', $ors );
                            break;
                        }
                    }
                }
                else
                {
                    $this->where[] = "MATCH({$meta['match']}) AGAINST( '$search' IN BOOLEAN MODE )";
                }
                break;
            }
        }
    }

    function _gen_sort()
    {
        $args =& $this->args;

        if( !empty($args['ids']) || !empty($args['nosort'])  )
        {
            $this->sql_p['order'] = empty($args['playlist']) ? '' : 'cart_item_order';
            return;
        }

        if( ($args['datasource'] == 'uploads') && ($args['sort'] == 'rank') )
        {
            $this->sql_p['columns'] = '((upload_num_scores*4) + (upload_num_playlists*2) + (upload_num_plays/2)) AS qrank';
        }

        $sorts = $this->GetValidSortFields();

        if( !empty($sorts[$args['sort']]) )
        {
            $args['ord'] = empty($args['ord']) || (strtoupper($args['ord']) == 'DESC') ? 'DESC' : 'ASC';

            $this->sql_p['order'] = $sorts[$args['sort']][1] . ' ' . $args['ord'];
        }

    }
    
    /*
    * List the sources of an upload (see also remix filter in cc-filter.php)
    */
    function _gen_sources()
    {
        switch( $this->args['datasource'] )
        {
            case 'uploads':
                $this->_heritage_helper('sources','tree_parent','cc_tbl_tree','tree_child','upload_id');
                break;
            case 'pool_items':
                $this->_heritage_helper('sources','pool_tree_pool_parent','cc_tbl_pool_tree','pool_tree_child','pool_item_id');
                break;
            default:
                die('invalid datasource for "sources"');
                break;
        }
    }

    function _gen_tags()
    {
        $this->tags = preg_split('/[\s,+]+/',$this->args['tags'],-1,PREG_SPLIT_NO_EMPTY);
    }

    function _gen_reqtags()
    {
        $this->reqtags = preg_split('/[\s,+]+/',$this->args['reqtags'],-1,PREG_SPLIT_NO_EMPTY);
    }

    function _gen_thread()
    {
        if( $this->args['datasource'] == 'topics' )
        {
            $thread = $this->args['thread'];
            if( $thread == -1 )
                $this->where[] = "topic_thread > 0";
            else
                $this->where[] = "topic_thread = $thread";
        }
    }

    function _gen_type()
    {
        // 'type' for uploads (as applied to tags) are handled elsewhere (see call to MakeTagFilter in this file)

        if( $this->args['datasource'] == 'topics' )
        {
            $this->where[] = "topic_type = '{$this->args['type']}'";
        }
    }

    function _gen_upload()
    {
        if( $this->args['datasource'] == 'topics' || ($this->args['datasource'] == 'ratings'))
        {
            $field = $this->_make_field('upload');
            $this->where[] = $field ." = '{$this->args['upload']}'";
        }
    }

    function _gen_user()
    {
        $user = $this->args['user'];
        if( $user{0} == '-' )
        {
            $user = substr($user,1);
            $op = '<>';
        }
        else
        {
            $op = '=';
        }

        if( $this->args['datasource'] == 'pool_items' )
        {
            $field = 'pool_item_artist';
            $user_id = $user;
        }
        else
        {
            $w['user_name'] = $user;
            $users =& CCUsers::GetTable();
            $user_id = $users->QueryKey($w);
            if( $this->args['datasource'] == 'user' )
                $field = 'user_id';
            else
                $field = $this->_make_field('user');
        }
        $this->where[] = "($field $op '{$user_id}')";
    }

    function _gen_visible()
    {
        if( !empty($this->_ignore_visible) )
            return;

        $need_user = false; // if the user is not an admin they
                            // should only see their uploads.
                            // this flag controls that filter

        $banned = 0; // default for banned upload = off

        if( !empty($this->args['mod']) )
        {
            // user requested banned uploads 
            if( CCUser::IsAdmin() )
            {
                $banned = 1;
            }
            else
            {
                if( CCUser::IsLoggedIn() )
                {
                    $banned = 1;
                    $need_user = true;
                }
            }
        }

        $published = 1; // default for published upload = on
        
        if( !empty($this->args['unpub']) )
        {
            // user requested unpublished uploads

            if( CCUser::IsAdmin() )
            {
                $published = 0;
            }
            else
            {
                if( CCUser::IsLoggedIn() )
                {
                    $published = 0;
                    $need_user = true;
                }
            }
        }

        // we special case for when both mod=1 and unpub=1
        // to make sure we return both 

        $op = ( $banned && !$published ) ? ' OR ' : ' AND ';

        $this->where[] = "(upload_banned = $banned $op upload_published = $published)";

        if( $need_user )
            $this->where[] = '(upload_user='.CCUser::CurrentUser().')';

    }

    function _heritage_helper($key,$f1,$t,$f2,$kf)
    {
        $id = sprintf('%0d',$this->args[$key]);
        // sigh, I can't get subqueries to work.
        $sql = "SELECT $f1 as $kf FROM $t WHERE $f2 = $id";
        $rows = CCDatabase::QueryItems($sql);
        if( empty($rows) )
        {
            //$this->where[] = $kf . ' IN (' . $sql . ')';
            $this->dead = true;
        }
        else
        {
            $this->where[] = $kf . ' IN (' . join(',',$rows) . ')';
        }
    }


    function _arg_alias()
    {
        $this->_arg_alias_ref($this->args);
    }

    function _arg_alias_ref(&$args)
    {
        $aliases = array( 'f'      => 'format',
                          't'      => 'template',
                          'm'      => 'template',
                          'macro'  => 'template',
                          'tmacro' => 'template',
                          'u'      => 'user',
                          'q'      => 'search',
                          'query'  => 'search',
                          's'      => 'search',
                       );

        foreach( $aliases as $short => $long )
        {
            if( isset($args[$short]) )
            {
                $args[$long] = $args[$short];
                unset( $args[$short] );
            }
        }
    }

    /**
     * @access private
     */
    function _make_field($field)
    {
        // yes, special case hacks go here
        if( ($field =='date') && ($this->args['datasource'] == 'user') )
            return 'user_registered';

        return preg_replace('/s?$/', '', $this->args['datasource']) . '_' . $field;
    }

    function _resort_records(&$records,&$sort_order,$sort_key)
    {
        if( !empty($sort_order) )
        {
            $sorted = array();
            $count = count($records);
            for( $i = 0; $i < $count; $i++ )
            {
                $sorted[ $sort_order[ $records[$i][$sort_key] ] ] = $records[$i];
            }
            $records = $sorted;
            $sorted = null;
            ksort($records);
        }
    }

    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('api','query'),   array( 'CCQuery', 'QueryURL'), 
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '', _('Browser query interface'), CC_AG_QUERY );

        cc_tcache_kill(); // this is probably an ?update=1 so kill the cache...
    }

    /**
    * Event handler for {@link CC_EVENT_GET_CONFIG_FIELDS}
    *
    * Add global settings settings to config editing form
    * 
    * @param string $scope Either CC_GLOBAL_SCOPE or CC_LOCAL_SCOPE
    * @param array  $fields Array of form fields to add fields to.
    */
    function OnGetConfigFields($scope,&$fields)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $fields['querylimit'] =
               array(  'label'      => _('Limit Queries'),
                       'form_tip'   => _("Limit the number of records returned from api/query (0 or blank means unlimited - HINT: that's a bad idea)"),
                       'value'      => 20,
                       'class'      => 'cc_form_input_short',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE );
        }
    }

    function GetValidSortFields()
    {
        // this method is a little shaky...

        if( $this->args['datasource'] == 'pool_items' )
        {
            return array( 'name' => array( _('Pool item name'), 'pool_item_name' ),
                          'user' => array( _('Pool item artist'), 'pool_item_artist' ),
                          'id'   => array( _('Internal id'), 'pool_item_id'),
                        );
        }

        if( $this->args['datasource'] == 'topics' )
        {
            return array( 'name' => array( _('Topic name'), 'topic_name' ),
                          'date' => array( _('Topic date'), 'topic_date' ),
                          'type' => array( _('Topic type'), 'topic_type' ),
                          'left' => array( _('Topic tree'), 'topic_left' ),
                        );
        }

        $user = array( 'fullname' => array( _('Aritst display name'),  'TRIM(LOWER(user_real_name))' ),
                          'date'     => array( _('Registration date'),  'user_registered' ),
                        'user'               => array( _('Artist login name'), 'user_name'),
                        'registered'         => array( _('Artist registered'), 'user_registered'),
                        'user_remixes'       => array( _('Number of remixes'), 'user_num_remixes'),
                        'remixed'            => array( _('Number of times remixed'), 'user_num_remixed'),
                        'uploads'            => array( _('Number of uploads'), 'user_num_uploads'),
                        'userscore'          => array( _('Artists\'s average rating'), 'user_score'),
                        'user_num_scores'    => array( _('Number of ratings'), 'user_num_scores'),
                        'user_reviews'       => array( _('Reviews left by artist'), 'user_num_reviews'),
                        'user_reviewed'      => array( _('Reviews left for artist'), 'user_num_reviewed'),
                        'posts'              => array( _('Forum topics by artist'), 'user_num_posts'),
                        );

        if( $this->args['datasource'] == 'user' )
        {
            return $user;
        }

        if( ($this->args['datasource'] == 'collabs') ||
            ($this->args['datasource'] == 'collab_user') )
        {
            return array_merge( $user, 
                       array( 'name' => array( _('Collaboration name'), 'collab_name' ),
                              'date' => array( _('Collaboration  date'), 'collab_date' ),
                              'user' => array( _('Collaboration owner'), 'collab_user' ),
                        ) );
        }

        if( $this->args['datasource'] != 'uploads' )
            return '';

        
        return array_merge( $user, array(
            'name'               => array( _('Upload name'),             'TRIM(TRIM(BOTH \'"\' FROM LOWER(upload_name)))'),
            'lic'                => array( _('Upload license'),          'upload_license'),
            'date'               => array( _('Upload date'),             'upload_date'),
            'last_edit'          => array( _('Upload last edited'),      'upload_last_edit'),
            'remixes'            => array( _('Upload\'s remixes'),       '(upload_num_remixes+upload_num_pool_remixes)'),
            'sources'            => array( _('Upload\'s sources'),       '(upload_num_sources+upload_num_pool_sources)'),
            'num_scores'         => array( _('Number of ratings'),       'upload_num_scores'),
            'num_playlists'      => array( _('Number of playlists'),     'upload_num_playlists desc,upload_date'),
            'id'                 => array( _('Internal upload id'),      'upload_id'),
            'local_remixes'      => array( _('Upload\'s local remixes'), 'upload_num_remixes'),
            'pool_remixes'       => array( _('Upload\'s remote remixes'),'upload_num_pool_remixes'),
            'local_sources'      => array( _('Upload\'s local sources'), 'upload_num_sources'),
            'pool_sources'       => array( _('Upload\'s sample pool sources'), 
                                                    'upload_num_pool_sources'),

            'rank'               => array( _('Upload Rank'), 'qrank'),
            'score'              => array( _('Upload\'s ratings'), 'upload_score'),
            ));
    }


} // end of class CCQuery


/**
* @private
*/
function cc_tcache_kill()
{
    $files = glob(cc_temp_dir() . '/query_cache_*.txt');
    foreach( $files as $file )
        unlink($file);
}


?>
