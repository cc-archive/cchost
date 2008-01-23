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
* @package cchost
* @subpackage api
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

require_once('cclib/cc-tags.php');

/**
*
*  these args should be generic stuff...
*
*  required for these to work is a genericized version of the following
*  fields:
*
*  _tags, _date, _id, _user, concat(* search fields)
*
*  args:
*
*  tag filters: tags, reqtags, type,             
* date filters: sinceu, sinced
* gen. filters: ids, user, search (req. 'qsearch' field in dataview)
*     ordering: sort, ord, nosort, rand
*       paging: limit, offset
*     data/fmt: format, datasource, template (for format=html/json/etc), title (for format=page)
*  
*
* These are upload specific:
*
*  promot_tag, promo_gap, lic, score, remixesof, remixedby, 
*  mod, unsub, playlist, remixmax, remixmin
*
*/
class CCQuery 
{
    function CCQuery()
    {
        $this->sql = '';
        $this->sql_p = array(   
                        'columns' => '',
                        'joins' => '',
                        'where' => '',
                        'order ' => '',
                        'limit' => '', 
                        'group_by' => '' );
        $this->where = array();
        $this->args = array();
        $this->records = array();
    }

    function QueryURL()
    {
        $this->ProcessUriArgs();

        // ------------------------------------------------------
        // Do the query
        //
        // This method MAY exit the session... 
        //
        list( $value, $mime ) = $this->Query();

        // ------------------------------------------------------
        // Results
        //

        if( $value === true ) // handled elsewhere 
            return;           // return and show the page

        // We didn't find anything, slap back a 404
        //
        if( empty($value) ) 
            CCUtil::Send404(true);

        if( !empty($mime) )
            header( "Content-type: $mime" );

        print($value);
        exit;
    }

    function Query($args=array())
    {
        if( !empty($args) )
            $this->args = $args;

        $this->_ensure_template();

        if( $this->args['datasource'] == 'uploads' )
            $this->_gen_visible();

        if( empty($this->args['cache']) )
        {
            $this->_generate_records();
        }
        else
        {
            $cname = cc_temp_dir() . '/query_cache_' . $args['cache'] . '.txt';
            if( file_exists($cname) )
            {
                include($cname);
                $this->records =& $_cache_rows;
                $this->_generate_records();
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
        return $this->_process_records();
    }

    function QuerySQL($qargs,$sqlargs)
    {
        $this->args = $qargs;
        $this->_ensure_template();        
        $this->sql_p = array_merge($this->sql_p,$sqlargs);
        $this->_gen_limit();
        $this->_gen_sort();
        $this->_common_query();
        return $this->_process_records();
    }

    function ProcessUriArgs($extra_args = array())
    {
        global $CC_GLOBALS;

        $req = !empty($_POST) ? $_POST : $_GET;

        if( empty($req) )
            return $extra_args;

        if( !empty($req['ccm']) )
            unset($req['ccm']);

        // ------------------------------------------------------
        // Security
        //
        if( !empty($req['where']) )
            unset($req['where']);

        CCUtil::Strip($req);

        // ------------------------------------------------------
        // Build up args..
        //
        // 1. System defaults...
        // 2. End-user...
        // 3. Calling code can override (important to make sure 
        //    things like 'limit' aren't abused)

        $this->args = array_merge($this->GetDefaultArgs(),$req,$extra_args);

        // alias short to long
        $this->_arg_alias();

        // get the '+' out of the tag str
        if( !empty($this->args['tags']) )
            $this->args['tags'] = str_replace( ' ', ',', urldecode($this->args['tags']));

        // queries might need decoding
        if( !empty($this->args['query']) )
            $this->args['query'] = urldecode($this->args['query']);

        $this->_check_limit();
        $this->_get_get_offset();

        $k = array_keys($this->args);
        $n = count($k);
        for( $i = 0; $i < $n; $i++)
        {
            $tt = $this->args[$k[$i]];
            if( is_string($tt) && (strpos($tt,'\'') === (strlen($tt)-1) ) )
                die('Illegal value in query');
        }
    
        return $this->args;
    }

    function ProcessAdminArgs($args,$extra_args=array(),$check_limit=true)
    {
        if( is_string($args) )
            parse_str($args,$args);

        $this->args = array_merge($this->GetDefaultArgs(),$args,$extra_args);

        // alias short to long
        $this->_arg_alias();

        if( !empty($this->args['tags']) )
        {
            // clean up tags 
            require_once('cclib/cc-tags.php');
            $this->args['tags'] = join(',',CCTag::TagSplit($this->args['tags']));
        }

        if( $check_limit )
            $this->_check_limit();

        $this->_get_get_offset();

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

        $badargs = array( 'qstring', 'ccm', 'view', 'format', 'template', 'template_args', 'dataview' ); 

        foreach( $keys as $K )
        {
            // I have to believe skipping qstring is the right thing here...
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

        $limit = empty($CC_GLOBALS['querylimit']) ? 10 : $CC_GLOBALS['querylimit'];

        return array(
                    'sort' => 'date', 'ord'  => 'DESC', 
                    'limit' => $limit, 'offset' => 0,
                    'datasource' => 'uploads', 'format' => 'page',
                    'promo_tag' => 'site_promo',  'promo_gap' => 4,
                    );
    }

    function _ensure_template()
    {
        // todo: move this out
        if( empty($this->args['template']) )
        {
            switch( $this->args['format'] )
            {
                case 'page':
                    $this->args['template'] = 'list_files';
                    break;
                case 'playlist':
                    $this->args['template'] = 'playlist_show_one';
                    break;
                case 'rss':
                    if( $this->args['datasource'] == 'uploads' )
                        $this->args['template'] = 'rss_20';
                    elseif( $this->args['datasource'] == 'topics' )
                        $this->args['template'] = 'rss_20_topics';
                    break;
                case 'atom':
                    $this->args['template'] = 'atom_10';
                    break;
                case 'xspf':
                    $this->args['template'] = 'xspf_10';
                    break;
            }
        }
    }

    function _generate_records()
    {
        foreach( array( 'sort', 'date', ) as $arg )
        {
            $method = '_gen_' . $arg;
            $this->$method();
        }

        foreach( array( 'search', 'tags', 'type', 'playlist', 'limit', 'ids', 'user', 'remixes', 'sources', 
                         'remixesof', 'score', 'lic', 'remixmax', 'remixmin', 'reccby',  'upload', 'thread',
                         'reviewee', 'match', 'reqtags','rand'
                        ) as $arg )
        {
            if( isset($this->args[$arg]) )
            {
                $method = '_gen_' . $arg;
                $this->$method();
            }
        }

        $this->_common_query();
    }

    function _common_query()
    {
        $this->_setup_dataview();

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

        $this->sql_p['where'] = join( ' AND ', $this->where );

        if( empty($this->dead) )
            $this->records =& $this->_perform_sql();


        // ------------- DUMP RESULTS ---------------------

        if( !empty($this->args['dump_query']) && CCUser::IsAdmin() )
        {
            $x[] = $this;
            $x[] =& $this->records;
            CCDebug::Enable(true);
            CCDebug::PrintVar($x,false);
        }

        if( !empty($_REQUEST['dump_rec']) && CCUser::IsAdmin() )
        {
            CCDebug::Enable(true);
            CCDebug::PrintVar($this->records[0],false);
        }

    }

    function _process_records()
    {
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
            $ids = is_string($this->args['ids']) ? split(',',$this->args['ids']) : $this->args['ids'];
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

    function _setup_dataview()
    {
        $this->dataview = new CCDataView();

        if( empty($this->args['dataview']) )
        {
            if( $this->args['format'] == 'count' )
            {
                $this->args['dataview'] = 'count';
                $this->dataviewProps = array( 'dataview' => 'count', 'file' => 'ccdataviews/count.php');
                $this->sql_p['limit'] = '';
            }
            elseif( $this->args['format'] == 'ids' )
            {
                $this->args['dataview'] = 'ids';
                $this->dataviewProps = array( 'dataview' => 'default', 'file' => 'ccdataviews/ids.php');
            }
            elseif( empty($this->args['template']) )
            {
                if( $this->args['format'] == 'm3u' )
                {
                    // yea,this kind of special knowledge should probably be 
                    // somewhere else - if we were pretending that we're not ccHost
                    $this->args['dataview'] = 'files';
                    $this->dataviewProps = array( 'dataview' => 'files', 'file' => 'ccdataviews/files.php');
                }
                else
                {
                    $this->args['dataview'] = 'default';
                    $this->dataviewProps = array( 'dataview' => 'default', 'file' => 'ccdataviews/default.php');
                }
            }
            else
            {
                $props = $this->dataview->GetDataViewFromTemplate($this->args['template']);
                $this->args['dataview'] = $props['dataview'];
                $this->dataviewProps = $props;
            }
        }
    }

    function & _perform_sql()
    {
        if( empty($this->args['dataview']) )
            die('No dataview');

        if( empty($this->dataviewProps) )
        {
            $props = $this->dataview->GetDataView($this->args['dataview']);
            $this->dataviewProps = $props;
        }
        $rettype = empty($this->args['rettype']) ? ($this->args['format'] == 'count' ? CCDV_RET_ITEM : CCDV_RET_RECORDS) : $this->args['rettype'];
        $records =&  $this->dataview->Perform( $this->dataviewProps, $this->sql_p, $rettype, $this );
        $this->sql =  $this->dataview->sql;
        return $records;
    }

    /********************************
    * Generators
    *********************************/
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
            $since = $this->args['sinceu'];
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
        // A specific set of IDs

        // this will do a security check in case someone tries
        // to escape out of sql
        $ids = array_unique(preg_split('/([^0-9]+)/',$this->args['ids'],0,PREG_SPLIT_NO_EMPTY));
        if( $ids )
        {
            $field = $this->_make_field('id');
            $this->where[] = "($field IN (" . join(',',$ids) . '))';
        }
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
        if( empty($this->args['offset']) )
            $this->args['offset'] = '0';
        $this->sql_p['limit'] = $this->args['limit'] . ' OFFSET ' . $this->args['offset'];
    }

    function _gen_match()
    {
        // this only works for specific dataviews (see search_remix_artist.php)
        $this->sql_p['match'] = addslashes(trim($this->args['match']));
    }

    function _gen_playlist()
    {
        if( $this->args['datasource'] == 'uploads' )
            $this->sql_p['joins'] = 'cc_tbl_cart_items ON cart_item_upload=upload_id';

        $this->where[] = 'cart_item_cart = '.$this->args['playlist']; // err, is this right?
    }

    function _gen_rand()
    {
        $this->sql_p['order'] = 'RAND()';
    }

    function _gen_reccby()
    {
        $user_id = CCDatabase::QueryItem("SELECT user_id FROM cc_tbl_user WHERE user_name= '{$this->args['reccby']}'");
        $this->sql_p['joins'] = 'cc_tbl_ratings ON ratings_upload=upload_id';
        $this->where[] = 'ratings_user = ' . $user_id;
        if( $this->args['format'] != 'count' )
        {
            $this->sql_p['order'] = 'ratings_id DESC'; // er, ....
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
        if( $this->args['datasource'] == 'user' )
            $this->where[] = 'user_num_scores >= ' . $this->args['score'];
        else
            $this->where[] = 'upload_num_scores >= ' . $this->args['score'];
    }

    function _gen_search()
    {
        $search_meta = array();
        CCEvents::Invoke( CC_EVENT_SEARCH_META, array(&$search_meta) );
        foreach( $search_meta as $meta )
        {
            if( $this->args['datasource'] == $meta['datasource'] )
            {
                $search = addslashes(trim($this->args['search']));
                $this->where[] = "MATCH({$meta['match']}) AGAINST( '$search' IN BOOLEAN MODE )";
                break;
            }
        }
    }

    function _gen_sort()
    {
        $args =& $this->args;

        if( !empty($args['ids']) || !empty($args['nosort'])  )
        {
            $this->sql_p['order'] = '';
            return;
        }

        if( ($args['datasource'] == 'uploads') && ($args['sort'] == 'rank') )
        {
            $this->sql_p['columns'] = '((upload_num_scores*4) + (upload_num_playlists*2) + (upload_num_plays/2)) AS qrank';
        }


        $sorts = $this->GetValidSortFields();

        if( !empty($sorts[$args['sort']]) )
        {
            if( empty($args['ord']) )
                $args['ord'] = 'ASC';

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
        if( $this->args['datasource'] == 'topics' )
        {
            $this->where[] = "topic_upload = '{$this->args['upload']}'";
        }
    }

    function _gen_user()
    {
        $w['user_name'] = $this->args['user'];
        $users =& CCUsers::GetTable();
        $user_id = $users->QueryKey($w);
        $field = $this->_make_field('user');
        $this->where[] = "($field = '{$user_id}')";
    }

    function _gen_visible()
    {
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
        $id = $this->args[$key];
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
        $args =& $this->args;

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

        cc_tcache_kill(); // this is probably and ?update=1 so kill the cache...
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
                        );
        }

        if( $this->args['datasource'] == 'topics' )
        {
            return array( 'name' => array( _('Topic name'), 'topic_name' ),
                          'date' => array( _('Topic date'), 'topic_date' ),
                          'type' => array( _('Topic type'), 'topic_type' )
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

        if( $this->args['datasource'] == 'collab' )
        {
            return array_merge( $user, 
                       array( 'name' => array( _('Collab name'), 'collab_name' ),
                              'date' => array( _('Topic date'), 'collab_date' ),
                            ));
        }



        if( $this->args['datasource'] != 'uploads' )
            return '';

        
        return array_merge( $user, array(
            'name'               => array( _('Upload name'), 'TRIM(TRIM(BOTH \'"\' FROM LOWER(upload_name)))'),
            'lic'                => array( _('Upload license'), 'upload_license'),
            'date'               => array( _('Upload date'), 'upload_date'),
            'last_edit'          => array( _('Upload last edited'), 'upload_last_edit'),
            'remixes'            => array( _('Upload\'s remixes'), 
                                                  '(upload_num_remixes+upload_num_pool_remixes)'),
            'sources'            => array( _('Upload\'s sources'),  
                                                   '(upload_num_sources+upload_num_pool_sources)'),

            'num_scores'         => array( _('Number of ratings'), 'upload_num_scores'),

            'id'                 => array( _('Internal upload id'), 'upload_id'),

            'local_remixes'      => array( _('Upload\'s local remixes'), 'upload_num_remixes'),
            'pool_remixes'       => array( _('Upload\'s remote remixes'), 
                                                   'upload_num_pool_remixes'),
            'local_sources'      => array( _('Upload\'s local sources'), 'upload_num_sources'),
            'pool_sources'       => array( _('Upload\'s sample pool sources'), 
                                                    'upload_num_pool_sources'),

            'rank'               => array( _('Upload Rank'), 'qrank'),
            'score'              => array( _('Upload\'s ratings'), 'upload_score'),
            ));
    }


    function _get_get_offset()
    {
        if( $this->args['format'] == 'page' && !empty($_GET['offset']) )
            $this->args['offset'] = sprintf('%0d',$_GET['offset']);
    }


    function _check_limit()
    {
        if( !empty($this->limit_override) )
            return;

        global $CC_GLOBALS;

        $args =& $this->args;

        if( $args['format'] == 'page' )
        {
            $admin_limit  = empty($CC_GLOBALS['max-listing']) ? 12 : $CC_GLOBALS['max-listing'];
        }
        elseif( in_array($args['format'], array('rss','atom','xspf') ) )
        {
            $admin_limit  = empty($CC_GLOBALS['max-feed']) ? 15 : $CC_GLOBALS['max-feed'];
        }
        else
        {
            $admin_limit = empty($CC_GLOBALS['querylimit']) ? 0 : $CC_GLOBALS['querylimit'];
        }

        if( !empty($admin_limit) && (empty($args['limit']) || ($admin_limit < $args['limit'])) )
        {
            $args['limit'] = $admin_limit;
        }
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
