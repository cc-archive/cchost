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

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCQuery',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCQuery' , 'OnGetConfigFields') );

/**
*/
class CCQuery 
{
    function QueryURL()
    {
        $args = $this->ProcessUriArgs();

        // ------------------------------------------------------
        // Do the query
        //
        // This method MAY exit the session... (like with feeds)
        //
        list( $value, $mime ) = $this->Query($args);

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

        $req = !empty($_POST['format']) ? $_POST : $_GET;

        if( empty($req) )
            return $extra_args;

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

        $args = $this->GetDefaultArgs();

        // 2. End-user...

        $args = array_merge($args,$req);
        
        // 3. Calling code can override (important to make sure 
        //    things like 'limit' aren't abused)

        $args = array_merge($args,$extra_args); 

        // alias short to long
        $this->_arg_alias($args);

        // get the '+' out of the tag str
        if( !empty($args['tags']) )
            $args['tags'] = str_replace( ' ', ',', urldecode($args['tags']));

        // queries might need decoding
        if( !empty($args['query']) )
            $args['query'] = urldecode($args['query']);

        $this->_check_limit($args);

        $k = array_keys($args);
        $n = count($k);
        for( $i = 0; $i < $n; $i++)
            if( is_string($args[$k[$i]]) && (strpos($args[$k[$i]],'\'') !== false) )
                die('Illegal value in query');
    
        if( !empty($sort) )
            $args['validated_sort'] = $this->_validate_sort_fields($sort);

        return $args;
    }

    function ProcessAdminArgs($args,$extra_args=array(),$check_limit=true)
    {
        if( is_string($args) )
            parse_str($args,$args);

        $args = array_merge($this->GetDefaultArgs(),$args);
        $args = array_merge($args,$extra_args); // Calling code can override 

        // alias short to long
        $this->_arg_alias($args);

        if( $args['tags'] )
        {
            // clean up tags 
            $args['tags'] = join(',',CCTag::TagSplit($args['tags']));
        }

        if( $check_limit )
            $this->_check_limit($args);
        $this->_get_get_offset($args);

        if( !empty($sort) )
            $args['validated_sort'] = $this->_validate_sort_fields($sort);

        return $args;
    }

    function SerializeArgs($args,$skip_format=true)
    {
        $keys = array_keys($args);
        $default_args = $this->GetDefaultArgs();
        $str = '';

        // alias short to long
        $this->_arg_alias($args);

        $fmtargs = array( 'format', 'template', 'tmacro', 'macro', 'paging' );
        foreach( $keys as $K )
        {
            // I have to believe skipping qstring is the right thing here...
            if( $K == 'qstring' || $K == 'ccm' || $K == 'view' )
                continue;

            if( $skip_format && in_array( $K, $fmtargs ) ) 
                continue;

            if( array_key_exists($K,$default_args) )
            {
                if( $args[$K] == $default_args[$K] )
                    continue;
            }
            if( empty($args[$K]) ) // um, is this right? what if 
                continue;          // it overrides a default for some random formatter?

            if( !empty($str) )
                $str .= '&';
            $str .= $K . '=' . $args[$K];
        }

        return $str;
    }

    function GetDefaultArgs()
    {
        global $CC_GLOBALS;

        $limit = empty($CC_GLOBALS['querylimit']) ? 0 : $CC_GLOBALS['querylimit'];

        return array(
                    'tags' => '',
                    'reqtags' => '',
                    'type' => '', // type will default to 'all' if tags are there
                                  // and 'phrase' if query is there

                    'promo_tag' => '',  // site_promo for ccMixter
                    'promo_gap' => 4,

                    'sort' => 'date',
                    'ord'  => 'DESC',
                    'nosort' => false,
                    'rand' => false,

                    'limit' => $limit,
                    'offset' => 0,

                    'sinceu' => 0,
                    'sinced' => 0,

                    'lic'    => 0,

                    'score' => 0,

                    'ids'    => '',
                    'user'  => '',
                    'remixesof' => '',
                    'remixedby' => '',

                    'where' => '',
                    'mod'   => 0,
                    'unsub' => 0,

                    'format' => 'page', 
                    'paging' => true,
                    'title'  => '',

                    'view' => 'upload'

                    );
    }

    function & _get_query_table($args)
    {
        // Get a new table so we can smash it about

        if( empty($args['remixesof']) )
        {
            // normally we create our own instance
            // and crush it
            $uploads = new CCUploads();
        }
        else
        {
            // here the query is done somewhere 
            // else so we take our chances that
            // the state will not affect too mucy
            // code
            $uploads =& CCUploads::GetTable();
        }

        $uploads->SetDefaultFilter(true,true); // query as anon

        return $uploads;
    }

    function _get_query_field($for)
    {
        switch( $for )
        {
            case 'date':
                return 'upload_date';
            case 'score':
                return 'upload_score';
            case 'search':
                return "upload_description,upload_tags,user_real_name,user_name,upload_name";
        }
        return '';
    }

    function _get_table_where($where,$uploads,$args)
    {
        $orgwhere = $where;
        extract($args);
        $where = $orgwhere;

        // vroot 

        if( !empty($vroot) )
        {
            $where[] = "(upload_config = '$vroot')";
        }

        // banned

        if( !empty($mod)  )
        {
            if( CCUser::IsAdmin() )
            {
                $uploads->SetDefaultFilter(false,false); 
                $where[] = '(upload_banned=1)';
            }
            else
            {
                CCUtil::Send404();
            }
        }

        // unpublished

        if( !empty($unpub) )
        {
            if( CCUser::IsAdmin() )
            {
                $uploads->SetDefaultFilter(false,false); 
                $where[] = '(upload_published<1)';
            }
            else
            {
                $uid = CCUser::CurrentUser();
                if( empty($uid) )
                {
                    CCUtil::Send404();
                }
                else
                {
                    $uploads->SetDefaultFilter(false,false); 

                    $where[] = "((upload_published<1) AND (upload_user=$uid))";
                }
            }
        }

        return $where;
    }

    function Query($args)
    {
        // do this before we start messing around with
        // the args...

        if( !isset( $args['qstring']) )
            $args['qstring'] = $this->SerializeArgs($args);

        $table =& $this->_get_query_table($args);

        extract($args);

        if( empty($format) )
            $format = 'page';

        // this is sort of a macro that expands here...

        if( !empty($remixedby) )
        {
            $user = $remixedby;

            if( empty($reqtags) )
                $reqtags = 'remix';
            elseif( !CCTag::InTag('remix',$reqtags) )
                $reqtags .= ',remix';
        }

        // sort

        if( empty($validated_sort) && !empty($sort) )
            $validated_sort = $this->_validate_sort_fields($sort);

        if( !empty($rand) )
        {
            $table->SetOrder('RAND()');
        }
        elseif( !empty($validated_sort) && empty($nosort) )
        {
            if( empty($ord) )
                $ord = 'ASC';
            $table->SetOrder($validated_sort,$ord);
        }

        // radio tag plugs

        $insert_promos = false;

        if( !empty($promo_tag) )
        {
            $temp_uploads = new CCUploads();
            $temp_uploads->SetOrder('RAND()');
            $temp_uploads->SetTagFilter($promo_tag); 
            $promos = $temp_uploads->GetRecords('');
            $insert_promos = !empty($promos);

            // initialize these rather than in the 
            // record loop below...
            $promo_recs = array();
            $promo = 0;
            $promo_count = count($promos);
            if( !empty($promos) )
                $promo_keys = array_keys($promos);
        }

        if( !empty($tags) )
        {
            if( empty($user) )
            {
                $contests =& CCContests::GetTable();
                $contest_names = $contests->QueryRows('','contest_short_name');
                $cnames = array();
                foreach( $contest_names as $contest_name )
                    $cnames[] = $contest_name['contest_short_name'];

                // one of the 'tags' may be a user name
                $users =& CCUsers::GetTable();
                $username = '';
                $tagarr = CCTag::TagSplit($tags);
                foreach( $tagarr as $tag )
                {
                    if( in_array(  $tag, $cnames ) )
                        continue;
                    $twhere['user_name'] = $tag;
                    if( $users->CountRows($twhere) == 1 )
                    {
                        $user = $tag;
                        $tags = join(',',array_diff( $tagarr, array($user) ));
                        break;
                    }
                }
            }

            if( !empty($tags) )
            {
                if( method_exists($table,'SetTagFilter') )
                {
                    if( (empty($type) || ($type == 'phrase')) )
                        $type = 'all';

                    $table->SetTagFilter($tags,$type);
                }
            }
        }

        if( !empty($limit) )
        {
            if( empty($offset) )
                $offset = 0;
            $table->SetOffsetAndLimit( $offset, $limit );
        }

        // ----------- WHERE ------------------

        if( empty($where) )
        {
            $where = array();
        }
        else 
        {
            $tempwhere = $where;
            $where = array();
            $where[] = $table->_where_to_string($tempwhere); // ugh sorry
        }

        if( !empty($reqtags) )
        {
            if( method_exists( $table, '_tags_to_where' ) )
            {
                // a bit sleazy but it works and will continue to 
                
                $tname = get_class($table);
                $dummyup = new $tname();
                if( method_exists( $dummyup, 'SetDefaultFilter') )
                    $dummyup->SetDefaultFilter(false); // shut all other filtering off
                $dummyup->SetTagFilter($reqtags,'all');
                $where[] = $dummyup->_tags_to_where(''); /* *cough* */
            }
        }

        if( !isset($ids) )
        {
            $ids = '';
        }
        elseif( !empty($ids) )
        {
            // this will do a security check in case someone tries
            // to escape out of sql
            $ids = array_unique(preg_split('/([^0-9]+)/',$ids,0,PREG_SPLIT_NO_EMPTY));
            $keyf = $table->_key_field;
            if( $ids )
                $where[] = "($keyf IN (" . join(',',$ids) . '))';
        }

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
            $datef = $this->_get_query_field('date');
            if( !empty($datef) )
                $where[] = "($datef > '$after')";
        }

        // Ratings...

        if( !empty($score) )
        {
            $scoref = $this->_get_query_field('score');
            if( !empty($scoref) )
                $where[] = "($scoref >= $score)";
        }

        // User...

        if( !empty($user) )
        {
            $where[] = "(user_name = '$user')";
        }

        // License 

        if( !empty($lic) )
        {
            $license = $this->_lic_query_to_key($lic);
            $where[] = "(license_id = '$license')";
        }

        // Search string 

        if( !empty($query) )
        {
            $columns = $this->_get_query_field('search');
            $where = $this->_build_search_query($type,$query,$columns,$where,$table);
        }

        $where = $this->_get_table_where($where,$table,$args);

        $where = join(' AND ', $where);

        // ------------- END WHERE ---------------------


        // ------------- DO THE QUERY ---------------------

        if( !empty($remixesof) )
        {
            // yes, this should live somewhere else

            $user_id = CCUser::IDFromName($remixesof);
            if( !empty($user_id) )
            {
                $remixes =& CCRemixSources::GetTable();
                $records =& $remixes->GetRemixesOf($user_id,$format=='count',$where);
            }
        }
        elseif( $format == 'count' )
        {
            $table->SetOffsetAndLimit( 0, 0 );
            $records = $table->CountRows($where);
        }
        elseif( $format == 'ids' )
        {
            $records = $table->QueryKeys($where);
        }
        elseif( !empty($cols) )
        {
            $columns = $this->_get_cols($cols);
            $records =& $table->QueryRows($where,$columns);
        }
        else
        {
            $records =& $table->GetRecords($where);
        }

        // ------------- END QUERY ---------------------

        if( !empty($dump_query) && CCUser::IsAdmin() )
        {
            $x[] = compact( array_keys($args) );
            $x[] = $uploads->_last_sql;
            $x[] = $records;
            CCDebug::PrintVar($x,false);
        }

        if( $format == 'count' )
        {
            return( array( "[$records]", 'text/plain' ) );
        }
        elseif( $format == 'ids' )
        {
            $text = empty($records) ? '-' : join(';',$records );

            return( array( $text, 'text/plain' ) );
        }

        // Do NOT return at this point if records are empty, 
        // an empty feed is still valid

        if( empty($records) )
            $records = array();

        if( !empty($records) && !empty($nosort) && !empty($ids) )
        {
            $i = 0;
            foreach($ids as $id)
                $sort_order[$id] = $i++;
            $this->_resort_records($records,$sort_order,$table->_key_field);
        }

        //---------------------------------------------
        // Clean up the sensitive fields and insert
        // any radio promos
        //
        $n = count($records);
        $rkeys = array_keys($records);
        for( $i = 0; $i < $n; $i++ )
        {
            $R =& $records[ $rkeys[$i] ];
            //$this->CleanRec($R,$format);
            
            if( $insert_promos )
            {
                if( !$i || ($i % $promo_gap == 0) )
                {
                    $p = $promos[ $promo_keys[$promo++ % $promo_count] ];
                    //$this->CleanRec($p,$format);
                    $promo_recs[] = $p;
                }
                $promo_recs[] = $R;
            }
        }

        if( $insert_promos )
            $records = $promo_recs;

        switch( $format )
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

                $args = compact( array_keys($args) );

                // used for paging and godknows what else
                $args['last_where'] = $table->_last_where; // here's a back door...

                CCEvents::Invoke( CC_EVENT_API_QUERY_FORMAT, 
                                    array( &$records, $args, &$results, &$results_mime ) );

                return array( $results, $results_mime );
            }
        } // end switch

        return array( &$records, '' );
    }

    function _arg_alias(&$args)
    {
        $aliases = array( 'f' => 'format',
                          't' => 'template',
                          'm' => 'macro',
                          'r' => 'remixinfo',
                          'u' => 'user',
                          'q' => 'query',
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
            CC_DONT_CARE_LOGGED_IN, ccs(__FILE__), '', _('Browser query interface'), CC_AG_SEARCH );
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
                       'form_tip'   => _('Limit the number of records returned from api/query (0
                                        or blank means unlimited - HINT: that\'s a bad idea)'),
                       'value'      => 200,
                       'class'      => 'cc_form_input_short',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE );
        }
    }

    function _lic_query_to_key($query_lic)
    {
        $tranlator = array( 
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

    function _validate_sort_fields($fields)
    {
        // this is at least partially done for security
        // reasons to avoid sql injection

        $valid = $this->GetValidSortFields();
        $out = array();

        $fields = preg_split('/[\s\+,]+/',$fields);
        foreach( $fields as $F )
        {
            if( empty($valid[$F][1]) )
                return null;
            $out[] = $valid[$F][1];
        }

        return '( ' . join(',',$out) . ') ';
    }

    function _get_get_offset(&$args)
    {
        if( empty($args['nogetoffset']) && !empty($_GET['offset']) )
            $args['offset'] = sprintf('%0d',$_GET['offset']);
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
    function _check_limit(&$args)
    {
        global $CC_GLOBALS;

        if( $args['format'] == 'page' )
        {
            $configs      =& CCConfigs::GetTable();
            $settings     = $configs->GetConfig('settings');
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
                'n' => 'upload_name',
                'u' => 'user_name,user_real_name',
                'd' => 'upload_date',
                'lu' => 'license_url,license_logo,upload_license',
                's' => 'upload_score'
            );
        $cols = array( 'upload_id', 'user_id' );
        foreach( $shorts as $short )
            if( !empty($t[$short]) )
                $cols[] = $t[$short];

        return join(',',$cols);
    }

} // end of class CCQuery


?>
