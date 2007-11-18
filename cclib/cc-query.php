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
        $this->sql_joins = '';
        $this->sql_where = '';
        $this->sql_sort  = '';
        $this->sql_limit = '';
        $this->where = array( '(upload_published>0 and upload_banned<1)' );
        $this->args = array();
    }

    function QueryURL()
    {
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

        $badargs = array( 'qstring', 'ccm', 'view', 'format', 'template');

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
                    'tags' => '', 'reqtags' => '', 'type' => '', 
                    'promo_tag' => '',  'promo_gap' => 4,
                    'sort' => 'date', 'ord'  => 'DESC', 'nosort' => false, 'rand' => false,
                    'limit' => $limit, 'offset' => 0,
                    'sinceu' => 0, 'sinced' => 0,
                    'lic'    => 0, 'score' => 0, 'ids' => '',  'user'  => '', 'remixesof' => '', 'remixedby' => '',
                    'where' => '', 'mod'   => 0, 'unsub' => 0, 'format' => 'page',  'title'  => '',
                    );
    }

    function _gen_sort()
    {
        $args =& $this->args;

        if( empty($this->validated_sort) && !empty($args['sort']) )
            $this->_validate_sort_fields();

        if( !empty($args['rand']) )
        {
            $this->sql_sort = 'ORDER BY RAND()';
        }
        elseif( !empty($this->validated_sort) && (empty($args['ids']) || empty($args['nosort']))  )
        {
            if( empty($args['ord']) )
                $args['ord'] = 'ASC';
            $this->sql_sort = 'ORDER BY ' . $this->validated_sort . ' ' . $args['ord'];
        }
        else
        {
            $this->sql_sort = '';
        }

    }
    
    function _gen_tags()
    {
    }

    function _gen_template()
    {
    }

    function _gen_playlist()
    {
    }

    function _gen_reccby()
    {
    }

    function _gen_remixesof()
    {
    }

    function _gen_limit()
    {
        if( empty($this->args['limit']) )
        {
            $this->sql_limit = '';
        }
        else
        {
            if( empty($this->args['offset']) )
                $this->args['offset'] = '0';
            $this->sql_limit = 'LIMIT ' . $this->args['limit'] . ' OFFSET ' . $this->args['offset'];
        }
    }

    function _gen_ids()
    {
        // A specific set of IDs

        if( !empty($this->args['ids']) )
        {
            // this will do a security check in case someone tries
            // to escape out of sql
            $this->args['ids'] = array_unique(preg_split('/([^0-9]+)/',$this->args['ids'],0,PREG_SPLIT_NO_EMPTY));
            if( $ids )
                $this->where[] = "(upload_id IN (" . join(',',$this->args['ids']) . '))';
        }

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

    function Query($args=array())
    {
        if( !empty($args) )
            $this->args = $args;

        if( !isset( $this->args['qstring']) )
            $this->args['qstring'] = $this->SerializeArgs($this->args);

        global $CC_GLOBALS;
        $CC_GLOBALS['qstring'] = $this->args['qstring'];

        if( empty($this->args['format']) )
            $this->args['format'] = 'page';

        $this->_gen_sort();
        $this->_gen_tags();
        $this->_gen_limit();
        $this->_gen_date();

        foreach( array( 'template', 'playlist', 'reccby', 'remixesof', 'ids' ) as $arg )
        {
            if( isset($args[$arg]) )
            {
                $method = '_gen_' . $arg;
                $this->$method();
            }
        }


        // Ratings...

        if( !empty($this->args['score']) )
            $this->where[] = "($scoref >= {$this->args['score']})";
        if( !empty($this->args['user']) )
            $this->where[] = "(user_name = '{$this->args['user']}')";
        if( !empty($this->args['lic']) )
        {
            $license = $this->_lic_query_to_key($this->args['lic']);
            $this->where[] = "(license_id = '$license')";
        }

        if( isset($remixmax) )
            $this->where[] = "(upload_num_remixes <= '$remixmax')";
        if( isset($remixmin) )
            $this->where[] = "(upload_num_remixes >= '$remixmin')";
        
        $this->sql_where = 'WHERE ' . join( ' AND ', $this->where );

        if( $this->args['format'] == 'count' )
        {
            $this->args['dataview'] = 'count';
            $this->dataview_file = 'ccskins/dataviews/count.php';
            $this->sql_limit = '';
        }
        elseif( $this->args['format'] == 'ids' )
        {
            $this->args['dataview'] = 'ids';
            $this->dataview_file = 'ccskins/dataviews/ids.php';
        }
        elseif( empty($this->args['dataview']) )
        {
            if( empty($this->args['template']) )
            {
                $this->args['dataview'] = 'default';
                $this->dataview_file = 'ccskins/dataviews/default.php';
            }
            else
            {
                require_once('cclib/cc-template.inc');
                $props = CCTemplateAdmin::GetDataView($this->args['template']);
                $this->args['dataview'] = $props['dataview'];
                $this->dataview_file = $props['file'];
            }
        }

        $records =& $this->_perform_sql();

        // ------------- END QUERY ---------------------

        if( !empty($dump_query) && CCUser::IsAdmin() )
        {
            $x[] = compact( array_keys($this->args) );
            $x[] =& $records;
            CCDebug::Enable(true);
            CCDebug::PrintVar($x,false);
        }

        if( !empty($_REQUEST['dump_rec']) && CCUser::IsAdmin() )
        {
            CCDebug::Enable(true);
            CCDebug::PrintVar($records[0]);
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

                // callers (like feeds) can pass through
                // arguments that will return in the event, but 
                // we should repack the args in case they changed
                // in this method...

                $args = $this->args;

                // used for paging and godknows what else
                $args['last_where'] = $this->sql_where;

                CCEvents::Invoke( CC_EVENT_API_QUERY_FORMAT, 
                                    array( &$records, $args, &$results, &$results_mime ) );

                return array( $results, $results_mime );
            }
        } // end switch

        return array( &$records, '' );
    }

    function & _perform_sql()
    {
        if( empty($this->args['dataview']) || empty($this->dataview_file) )
            die('No dataview');
        if( !file_exists($this->dataview_file) )
            die("Can't find dataview: " . $this->args['dataview']);
        require_once('cclib/cc-defines-filters.php');
        require_once($this->dataview_file);
        $func = $this->args['dataview'] . '_dataview';
        if( !function_exists($func) )
            die("Can't find dataview function in " . $this->args['dataview']);
        $info = $func();
        $sql = preg_replace( array( '/%joins%/', '/(WHERE)? %where%/e', '/%order%/', '/%limit%/', '/%columns%/'  ),
                             array( $this->sql_joins, 
                                    empty($this->sql_where) ? '"$1"' : '"' . $this->sql_where . '" . ("$1" ? "AND" : "")', 
                                    $this->sql_sort, 
                                    $this->sql_limit, 
                                    '' ),
                             $info['sql'] );

        $records = CCDatabase::QueryRows($sql);

        if( count($records) > 0 && !empty($info['e']) )
        {
            foreach( $info['e'] as $event )
            {
                CCEvents::Invoke( $event, array( &$records, &$this->args, &$info ) );
            }
        }

        return $records;        
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
                          'q'      => 'query',
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

    function CleanRec(&$R)
    {
        $fields = array( 'user_email', 'user_password', 'user_last_known_ip', 'user_extra',
                        'upload_taglinks', 'usertag_links', 'user_fields', 'upload_extra',
                        'local_menu', 'ratings', 'flag_url' );
        
        foreach( $fields as $f )
            if( isset($R[$f]) ) unset($R[$f]);

        if( !empty($R['files']) )
        {
            $keys = array_keys($R['files']);
            $fields = array( 'file_extra', 'local_path' );
            foreach( $keys as $key )
                foreach( $fields as $f )
                    if( isset($R['files'][$key][$f]) ) unset($R['files'][$key][$f]);
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
                       'value'      => 200,
                       'class'      => 'cc_form_input_short',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE );
        }
    }

    function _lic_query_to_key($query_lic)
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

        if( !array_key_exists( $query_lic, $translator ) )
            return '??';

        return $translator[$query_lic];
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

    function _build_search_query($types,$query,$columns,$where,$table)
    {
        $query = strtolower($query);

        if( empty($type) )
            $type = 'phrase';

        $qf = "LOWER(CONCAT_WS(' ', $columns))";
    
        $table->AddExtraColumn($qf . ' as qsearch');
        switch( $type )
        {
            case 'phrase':
                $where[] = "($qf LIKE '%$query%')";
                break;

            case 'any':
            case 'all':
                $qterms = $this->_get_search_terms($query);
                $qsearch = array();
                foreach( $qterms as $qt )
                    $qsearch[] = "($qf LIKE '%$qt%')";
                $where[] = '(' . join( $type == 'all' ? 'AND' : 'OR', $qsearch ) . ')';
                break;
        }

        return $where;
    }

    function _get_search_terms($query)
    {
        preg_match_all('/("(.*)"|(.*)(?:$|\s))/U',$query,$qterms );
        return array_filter( array_merge( $qterms[2], $qterms[3] ), 
                                   create_function('$t','return !empty($t);') );
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

    function _get_cols($str)
    {
        $shorts = preg_split('/[,\s+]/',$str);
        $t = array(
                'a' => 'upload_num_scores as a',
                'b' => '(upload_num_remixes+upload_num_pool_remixes) as b',
                'c' => '(upload_num_sources+upload_num_pool_sources) as c',
                'd' => 'upload_date as d',
                'i' => 'upload_id as i',
                'l' => 'upload_license as l',
                'n' => 'upload_name as n',
                'r' => 'user_real_name as r',
                's' => 'upload_score as s',
                't' => 'upload_tags as t',
                'u' => 'user_name as u',
            );
        $cols = array(); // array( 'upload_id', 'user_id' );
        foreach( $shorts as $short )
            if( !empty($t[$short]) )
                $cols[] = $t[$short];

        return join(',',$cols);
    }

} // end of class CCQuery


?>
