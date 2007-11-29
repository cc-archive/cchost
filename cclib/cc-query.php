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
*/
class CCQuery 
{
    function CCQuery()
    {
        $this->sql ='';
        $this->sql_columns = ''; 
        $this->sql_joins = '';
        $this->sql_where = '';
        $this->sql_order  = '';
        $this->sql_limit = '';
        $this->sql_group_by = '';
        $this->where = array();
        $this->args = array();
    }

    function QueryURL()
    {
        if( !empty($_GET['fix']) )
        {
            $sql[] = "ALTER TABLE `cc_tbl_user` ADD INDEX ( `user_name` ) ";
            $sql[] = "UPDATE cc_tbl_uploads SET upload_tags = CONCAT(',',upload_tags,',')";
            $sql[] = "ALTER TABLE `cc_tbl_uploads` ADD INDEX ( `upload_date` , `upload_tags` ( 200 ), `upload_published` , `upload_banned` ) ";
            $sql[] = "ALTER TABLE `cc_tbl_pool_tree` ADD INDEX ( `pool_tree_child` , `pool_tree_parent` ) ";
            $sql[] = "ALTER TABLE `cc_tbl_files` ADD INDEX ( `file_order` ) ";
            CCDatabase::Query($sql);
            print('fixed');
            exit;
        }


        $this->ProcessUriArgs();

        // ------------------------------------------------------
        // Do the query
        //
        // This method MAY exit the session... (like with feeds)
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

        $k = array_keys($this->args);
        $n = count($k);
        for( $i = 0; $i < $n; $i++)
            if( is_string($this->args[$k[$i]]) && (strpos($this->args[$k[$i]],'\'') !== false) )
                die('Illegal value in query');
    
        if( !empty($this->args['sort']) )
            $this->_validate_sort_fields();

        return $this->args;
    }

    function ProcessAdminArgs($args,$extra_args=array(),$check_limit=true)
    {
        if( is_string($args) )
            parse_str($args,$args);

        $this->args = array_merge($this->GetDefaultArgs(),$args,$extra_args);

        // alias short to long
        $this->_arg_alias();

        if( $this->args['tags'] )
        {
            // clean up tags 
            $this->args['tags'] = join(',',CCTag::TagSplit($this->args['tags']));
        }

        if( $check_limit )
            $this->_check_limit();
        $this->_get_get_offset();

        if( !empty($this->args['sort']) )
            $this->_validate_sort_fields();

        return $this->args;
    }

    function SerializeArgs()
    {
        $args =& $this->args;
        $keys = array_keys($args);
        $default_args = $this->GetDefaultArgs();
        $str = '';

        // alias short to long
        $this->_arg_alias();

        $badargs = array( 'qstring', 'ccm', 'view', 'format', 'template', 'datasource' );

        foreach( $keys as $K )
        {
            // I have to believe skipping qstring is the right thing here...
            if(  in_array( $K, $badargs ) || empty($args[$K]) || is_object($args[$K]) ||
                ( array_key_exists($K,$default_args) && ($args[$K] == $default_args[$K]) ) )
            {
                continue;
            }

            if( !empty($str) )
                $str .= '&';
            $str .= $K . '=' . $args[$K];
        }

        return $str;
    }

    function GetDefaultArgs()
    {
        global $CC_GLOBALS;

        $limit = empty($CC_GLOBALS['querylimit']) ? 10 : $CC_GLOBALS['querylimit'];

        return array(
                    'tags' => null, 'reqtags' => null, 'type' => null, 
                    'promo_tag' => null,  'promo_gap' => 4,
                    'sort' => 'date', 'ord'  => 'DESC', 'nosort' => false, 'rand' => false,
                    'limit' => $limit, 'offset' => 0,
                    'sinceu' => 0, 'sinced' => 0,
                    'lic'    => null, 'score' => null, 'ids' => null,  'user'  => null, 'remixesof' => null, 'remixedby' => null,
                    'where' => null, 'mod'   => 0, 'unsub' => 0, 'format' => 'page',  'title'  => null,
                    'datasource' => 'uploads',
                    );
    }

    function Query($args=array())
    {
        if( !empty($args) )
            $this->args = $args;

        if( empty($this->args['format']) )
            $this->args['format'] = 'page';

        if( $this->args['format'] == 'page' && empty($this->args['template'])  )
            $this->args['template'] = 'list_files';

        if( $this->args['format'] == 'playlist' && empty($this->args['template'])  )
            $this->args['template'] = 'playlist_show_one';

        if( $this->args['datasource'] == 'uploads' )
            $this->where[] = '(upload_published=1 and upload_banned=0)' ;

        foreach( array( 'sort', 'date', ) as $arg )
        {
            $method = '_gen_' . $arg;
            $this->$method();
        }

        foreach( array( 'search', 'tags', 'playlist', 'limit', 'ids', 'user', 'remixes', 'sources', 
                         'remixesof', 'score', 'lic', 'remixmax', 'remixmin', 'reccby',  ) as $arg )
        {
            if( isset($this->args[$arg]) )
            {
                $method = '_gen_' . $arg;
                $this->$method();
            }
        }

        $this->sql_where = 'WHERE ' . join( ' AND ', $this->where );

        $this->_setup_dataview();

        if( empty($this->dead) )
            $records =& $this->_perform_sql();
        else
            $records = array();

        // ------------- DUMP RESULTS ---------------------

        if( !empty($this->args['dump_query']) && CCUser::IsAdmin() )
        {
            $x[] = $this;
            $x[] =& $records;
            CCDebug::Enable(true);
            CCDebug::PrintVar($x,false);
        }

        if( !empty($_REQUEST['dump_rec']) && CCUser::IsAdmin() )
        {
            CCDebug::Enable(true);
            CCDebug::PrintVar($records[0],false);
        }

        if( $this->args['format'] == 'count' )
        {
            return( array( "[$records]", 'text/plain' ) );
        }
        elseif( $this->args['format'] == 'ids' )
        {
            $text = empty($records) ? '-' : join(';',$records );

            return( array( $text, 'text/plain' ) );
        }

        // Do NOT return at this point if records are empty, 
        // an empty feed is still valid

        if( empty($records) )
            $records = array();

        if( !empty($records) && !empty($this->args['nosort']) && !empty($this->args['ids']) )
        {
            $ids = $this->args['ids'];
            $i = 0;
            foreach($ids as $id)
                $sort_order[$id] = $i++;
            $this->_resort_records($records,$sort_order,'upload_id');
        }

        switch( $this->args['format'] )
        {
            case 'undefined':
            case 'php':
                break;

            case 'phps':
                return array( serialize($records), 'text/plain' );

            default:
            {
                $results = '';
                $results_mime = '';
                $this->args['queryObj'] = $this;

                CCEvents::Invoke( CC_EVENT_API_QUERY_FORMAT, 
                                    array( &$records, &$this->args, &$results, &$results_mime ) );

                return array( $results, $results_mime );
            }
        } // end switch

        return array( &$records, '' );
    }

    function _setup_dataview()
    {
        if( $this->args['format'] == 'count' )
        {
            if( $this->args['datasource'] == 'uploads' )
            {
                $this->args['dataview'] = 'count';
                $this->dataview = array( 'dataview' => 'count', 'file' => 'ccdataviews/count.php');
            }
            $this->sql_limit = '';
        }
        elseif( $this->args['format'] == 'ids' )
        {
            $this->args['dataview'] = 'ids';
            $this->dataview = array( 'dataview' => 'default', 'file' => 'ccdataviews/ids.php');
        }
        elseif( empty($this->args['dataview']) )
        {
            if( empty($this->args['template']) )
            {
                if( $this->args['format'] == 'm3u' )
                {
                    // yea,this kind of special knowledge should probably be 
                    // somewhere else - if we were pretending that we're not ccHost
                    $this->args['dataview'] = 'files';
                    $this->dataview = array( 'dataview' => 'files', 'file' => 'ccdataviews/files.php');
                }
                else
                {
                    $this->args['dataview'] = 'default';
                    $this->dataview = array( 'dataview' => 'default', 'file' => 'ccdataviews/default.php');
                }
            }
            else
            {
                $dv = new CCDataview();
                $props = $dv->GetDataViewFromTemplate($this->args['template']);
                $this->args['dataview'] = $props['dataview'];
                $this->dataview = $props;
            }
        }
    }

    function & _perform_sql()
    {
        if( empty($this->args['dataview']) )
            die('No dataview');
        $dv = new CCDataView();
        $args = array();
        foreach( array( 'where', 'order', 'joins', 'limit', 'columns', 'group_by' ) as $f )
        {
            $member = 'sql_' . $f;
            $args[$f] = $this->$member;
        }
        if( empty($this->dataview) )
        {
            $dv = new CCDataview();
            $props = $dv->GetDataView($this->args['dataview']);
            $this->dataview = $props;
        }
        $records =& $dv->Perform($this->dataview,$args,$this->args['format'] == 'count' ? CCDV_RET_ITEM : CCDV_RET_RECORDS,$this );
        $this->sql = $dv->sql;
        return $records;
    }

    /********************************
    * Generators
    *********************************/
    function _gen_search()
    {
        if( empty($this->args['dataview']) )
            $this->args['dataview'] = 'search';

        $query = strtolower($this->args['search']);
        $type = empty($this->args['type']) ? 'all' : $this->args['type'];

        switch( $type )
        {
            case 'phrase':
                $having = "(qsearch LIKE '%$query%')";
                break;

            case 'any':
            case 'all':
                preg_match_all('/("(.*)"|(.*)(?:$|\s))/U',$query,$qterms );
                $qterms = array_filter( array_merge( $qterms[2], $qterms[3] ), 
                                           create_function('$t','return !empty($t);') );
                $qsearch = array();
                foreach( $qterms as $qt )
                    $qsearch[] = "(qsearch LIKE '%$qt%')";
                $having = '(' . join( $type == 'all' ? 'AND' : 'OR', $qsearch ) . ')';
                break;
        }

        $this->sql_group_by = 'GROUP BY upload_id HAVING ' . $having;
    }


    function _gen_sort()
    {
        if( $this->args['datasource'] != 'uploads' )
        {
            $this->sql_order = '';
            return;
        }

        $args =& $this->args;

        if( empty($this->validated_sort) && !empty($args['sort']) )
            $this->_validate_sort_fields();

        if( !empty($args['rand']) )
        {
            $this->sql_order = 'ORDER BY RAND()';
        }
        elseif( !empty($this->validated_sort) && (empty($args['ids']) || empty($args['nosort']))  )
        {
            if( empty($args['ord']) )
                $args['ord'] = 'ASC';
            $this->sql_order = 'ORDER BY ' . $this->validated_sort . ' ' . $args['ord'];
        }
        else
        {
            $this->sql_order = '';
        }

    }
    
    function _gen_tags()
    {
        $tags = preg_split('/[\s,+]+/',$this->args['tags'],-1,PREG_SPLIT_NO_EMPTY);
        $twhere = array();
        foreach( $tags as $tag )
            $twhere[] = "(upload_tags LIKE '%,{$tag},%')";
        $j = $this->args['type'] == 'any' ? 'OR' : 'AND';

        $this->where[] = '(' . join($j,$twhere) . ')';
    }

    function _gen_playlist()
    {
    }

    function _gen_reccby()
    {
        $users =& CCUsers::GetTable();
        $w['user_name'] = $this->args['reccby'];
        $user_id = $users->QueryKey($w);
        if( $this->args['format'] == 'count' )
        {
            $this->where[] = 'ratings_user = ' . $user_id;
        }
        else
        {
            $sql = 'SELECT ratings_upload FROM cc_tbl_ratings WHERE ratings_user = ' . $user_id . ' ORDER BY ratings_id DESC ';
            $ids = CCDatabase::QueryItems($sql);
            if( $ids )
                $this->where[] = '(upload_id IN (' . join(',',$ids) . '))';
            else
                $this->dead = true;
        }
    }

    function _gen_limit()
    {
        if( empty($this->args['offset']) )
            $this->args['offset'] = '0';
        $this->sql_limit = 'LIMIT ' . $this->args['limit'] . ' OFFSET ' . $this->args['offset'];
    }

    function _gen_ids()
    {
        // A specific set of IDs

        // this will do a security check in case someone tries
        // to escape out of sql
        $ids = array_unique(preg_split('/([^0-9]+)/',$this->args['ids'],0,PREG_SPLIT_NO_EMPTY));
        if( $ids )
            $this->where[] = '(upload_id IN (' . join(',',$ids) . '))';
    }

    function _gen_date()
    {
        // Check for date limit

        $since = 0;

        if( !empty($sinced) )     // text date
        {
            $since = strtotime($sinced);
            if( $since < 1 )
                die('invalid date string');
        }
        elseif( !empty($sinceu) ) // unix time
        {
            if( $sinceu{0} === '_' )
                $sinceu = substr($sinceu,1);
            $since = $sinceu;
        }

        if( !empty($since) )
        {
            $after = date( 'Y-m-d H:i', $since );
            $this->where[] = "(upload_date > '$after')";
        }

    }

    function _gen_user()
    {
        $w['user_name'] = $this->args['user'];
        $users =& CCUsers::GetTable();
        $user_id = $users->QueryKey($w);
        $this->where[] = "(upload_user = '{$user_id}')";
    }

    function _heritage_helper($key,$f1,$t,$f2,$kf)
    {
        $id = $this->args[$key];
        // sigh, I can't get subqueries to work.
        $sql = "SELECT $f1 FROM $t WHERE $f2 = $id";
        $rows = CCDatabase::QueryItems($sql);
        if( empty($rows) )
        {
            $this->where[] = '0';
            $this->dead = true;
        }
        else
        {
            $this->where[] = $kf . ' IN (' . join(',',$rows) . ')';
        }
    }

    function _gen_remixes()
    {
        $this->_heritage_helper('remixes','tree_child','cc_tbl_tree','tree_parent','upload_id');
    }

    function _gen_sources()
    {
        if( $this->args['datasource'] == 'pools' )
            $this->_heritage_helper('sources','pool_tree_pool_parent','cc_tbl_pool_tree','pool_tree_child','pool_item_id');
        else
            $this->_heritage_helper('sources','tree_parent','cc_tbl_tree','tree_child','upload_id');
    }

    function _gen_remixesof()
    {
        $users =& CCUsers::GetTable();
        $where['user_name'] = $this->args['remixesof'];
        $user_id = $users->QueryKey($where);
        if( empty($user_id) )
        {
            $this->dead = true;
            return;
        }
        $sql = "SELECT tree_child FROM cc_tbl_tree JOIN cc_tbl_uploads ON tree_parent = upload_id WHERE upload_id = " . $user_id;
        $ids = CCDatabase::QueryItems($sql);
        if( empty($ids) )
        {
            $this->dead = true;
            return;
        }
        $this->where[] = 'upload_id IN (' . join(',',$ids) . ')';
    }


    function _gen_score()
    {
        $this->where[] = "(upload_num_scores >= {$this->args['score']})";
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
        $this->where[] = "(upload_license = '$license')";
    }

    function _gen_remixmax()
    {
        $this->where[] = "(upload_num_remixes <= '{$this->args['remixmax']}')";
    }

    function _gen_remixmin()
    {
        $this->where[] = "(upload_num_remixes >= '{$this->args['remixmin']}')";
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
        return array(
            'name'               => array( _('Upload name'), 'upload_name'),
            'lic'                => array( _('Upload license'), 'upload_license'),
            'date'               => array( _('Upload date'), 'upload_date'),
            'last_edit'          => array( _('Upload last edited'), 'upload_last_edit'),
            'remixes'            => array( _('Upload\'s remixes'), 
                                                  '(upload_num_remixes+upload_num_pool_remixes)'),
            'local_remixes'      => array( _('Upload\'s local remixes'), 'upload_num_remixes'),
            'pool_remixes'       => array( _('Upload\'s remote remixes'), 
                                                   'upload_num_pool_remixes'),
            'sources'            => array( _('Upload\'s sources'),  
                                                   '(upload_num_sources+upload_num_pool_sources)'),
            'local_sources'      => array( _('Upload\'s local sources'), 'upload_num_sources'),
            'pool_sources'       => array( _('Upload\'s sample pool sources'), 
                                                    'upload_num_pool_sources'),
            'score'              => array( _('Upload\'s ratings'), 'upload_score'),
            'num_scores'         => array( _('Number of ratings'), 'upload_num_scores'),
            'rank'               => array( _('Upload ranking'), 'upload_rank'),
            'user'               => array( _('Artist login name'), 'user_name'),
            'fullname'           => array( _('Artist name'), 'user_real_name'),
            'registered'         => array( _('Artist registered'), 'user_registered'),
            'user_remixes'       => array( _('Number of remixes'), 'user_num_remixes'),
            'remixed'            => array( _('Number of times remixed'), 'user_num_remixed'),
            'uploads'            => array( _('Number of uploads'), 'user_num_uploads'),
            'userscore'          => array( _('Artists\'s average rating'), 'user_score'),
            'user_num_scores'    => array( _('Number of ratings'), 'user_num_scores'),
            'userrank'           => array( _('Artist\'s ranking'), 'user_rank'),
            'user_reviews'       => array( _('Reviews left by artist'), 'user_num_reviews'),
            'user_reviewed'      => array( _('Reviews left for artist'), 'user_num_reviewed'),
            'posts'              => array( _('Forum topics by artist'), 'user_num_posts'),
            'id'                 => array( _('Internal upload id'), 'upload_id'),
            );
    }

    function _validate_sort_fields()
    {
        // this is at least partially done for security
        // reasons to avoid sql injection

        $fields = $this->args['sort'];
        if( empty($fields) )
            return;

        $valid = $this->GetValidSortFields();
        $out = array();

        $fields = preg_split('/[\s\+,]+/',$fields);
        foreach( $fields as $F )
        {
            if( empty($valid[$F][1]) )
                return null;
            $out[] = $valid[$F][1];
        }

        $this->validated_sort = '( ' . join(',',$out) . ') ';
    }

    function _get_get_offset()
    {
        if( !empty($_GET['offset']) )
            $this->args['offset'] = sprintf('%0d',$_GET['offset']);
    }


    function _check_limit()
    {
        global $CC_GLOBALS;

        $args =& $this->args;

        if( $args['format'] == 'page' )
        {
            $configs      =& CCConfigs::GetTable();
            $settings     = $configs->GetConfig('skin-settings');
            $admin_limit  = $settings['max-listing'];
        }
        else
        {
            $admin_limit = empty($CC_GLOBALS['querylimit']) ? 0
                            : $CC_GLOBALS['querylimit'];
        }

        if( !empty($admin_limit) && (empty($args['limit']) || ($admin_limit < $args['limit'])) )
        {
            $args['limit'] = $admin_limit;
        }
    }

} // end of class CCQuery


?>
