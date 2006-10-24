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
* RSS Module feed generator
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

        // for now disalow text queries from browser:
        if( !empty($req['q']) )
            unset($req['q']);

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

        // get the '+' out of the tag str
        $args['tags'] = str_replace( ' ', ',', urldecode($args['tags']));
        
        if
        ( 
            !empty($CC_GLOBALS['querylimit']) && 
            (
                empty($args['limit']) ||
                ($CC_GLOBALS['querylimit'] < $args['limit']) 
            )
        )
        {
            $args['limit'] = $CC_GLOBALS['querylimit'];
        }

        $k = array_keys($args);
        $n = count($k);
        for( $i = 0; $i < $n; $i++)
            if( is_string($args[$k[$i]]) && (strpos($args[$k[$i]],'\'') !== false) )
                die('Illegal value in query');

        return $args;
    }

    function GetDefaultArgs()
    {
        global $CC_GLOBALS;

        $limit = empty($CC_GLOBALS['querylimit']) ? 0 : $CC_GLOBALS['querylimit'];

        return array(
                    'tags' => '',
                    'reqtags' => '',
                    'type' => 'all',

                    'promo_tag' => '',  // site_promo for ccMixter
                    'promo_gap' => 4,

                    'sort' => 'upload_date',
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

                    'format' => 'php', 

                    );
    }

    function Query($args)
    {
        extract($args);

        if( empty($remixesof) )
        {
            // Get a new table so we can smash it about

            $uploads = new CCUploads();
        }
        else
        {
            // in this case the query will be done elsewhere
            // and we want to use the global table to set things
            // up like sort and tags
            // 
            // The global instance of the table will be pretty 
            // useless after that because it will have this 
            // query's state smashed into it, but oh well
            //
            $uploads = CCUploads::GetTable();
        }

        $uploads->SetDefaultFilter(true,true); // query as anon
        
        if( empty($format) )
            $format = 'undefined';

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

        if( !empty($rand) )
        {
            $uploads->SetOrder('RAND()');
        }
        elseif( !empty($sort) && empty($nosort) )
        {
            if( empty($ord) )
                $ord = 'ASC';
            $uploads->SetOrder($sort,$ord);
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
        }

        if( isset($tags) )
        {
            if( empty($user) )
            {
                // one of the 'tags' may be a user name
                $users =& CCUsers::GetTable();
                $username = '';
                $tagarr = CCTag::TagSplit($tags);
                foreach( $tagarr as $tag )
                {
                    $twhere['user_name'] = $tag;
                    if( $users->CountRows($twhere) == 1 )
                    {
                        $user = $tag;
                        $tags = join(',',array_diff( $tagarr, array($user) ));
                        break;
                    }
                }
            }
        }
        else
        {
            $tags = '';
        }

        if( empty($type) )
            $type = 'all';

        $uploads->SetTagFilter($tags,$type);

        if( !(empty($limit) && empty($offset)) )
        {
            if( empty($offset) )
                $offset = 0;
            $uploads->SetOffsetAndLimit( $offset, $limit );
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
            $where[] = $uploads->_where_to_string($tempwhere); // ugh sorry
        }

        if( !empty($reqtags) )
        {
            // a bit sleazy but it works and will continue to 
            
            $dummyup = new CCUploads();
            $dummyup->SetDefaultFilter(false); // shut all other filtering off
            $dummyup->SetTagFilter($reqtags,'all');
            $where[] = $dummyup->_tags_to_where(''); /* *cough* */
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
            if( $ids )
                $where[] = "(upload_id IN (" . join(',',$ids) . '))';
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
            // CCDebug::PrintVar($after);
            $where[] = "(upload_date > '$after')";
        }

        // Ratings...

        if( !empty($score) )
        {
            $where[] = "(upload_score >= $score)";
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

        if( !empty($q) )
        {
            $where[] = "(LOWER(CONCAT(upload_description,upload_tags,user_real_name,user_name,upload_name)) LIKE '%$q%'";
        }

        // banned

        if( !empty($mod)  )
        {
            if( CCUser::IsAdmin() )
            {
                $uploads->SetDefaultFilter(false,false); // query as anon
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
                $uploads->SetDefaultFilter(false,false); // query as anon
                $where[] = '(upload_published<1)';
            }
            else
            {
                $uploads->SetDefaultFilter(false,false); // query as anon
                $uid = CCUser::CurrentUser();

                $where[] = "((upload_published<1) AND (upload_user=$uid))";
            }
        }

        $where = join(' AND ', $where);

        // ------------- END WHERE ---------------------


        // ------------- DO THE QUERY ---------------------

        if( !empty($remixesof) )
        {
            $user_id = CCUser::IDFromName($remixesof);
            if( !empty($user_id) )
            {
                $remixes =& CCRemixSources::GetTable();
                $records =& $remixes->GetRemixesOf($user_id,$format=='count',$where);
            }
        }
        elseif( $format == 'count' )
        {
            $records = $uploads->CountRows($where);
        }
        elseif( $format == 'ids' )
        {
            $records = $uploads->QueryKeys($where);
        }
        else 
        {
            $records =& $uploads->GetRecords($where);
        }

        // ------------- END QUERY ---------------------

        if( $format == 'count' )
        {
            if( $limit )
                $records = min($records,$limit);

            return( array( "[$records][$limit]", 'text/plain' ) );
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
            $this->_resort_records($records,$sort_order);
        }

        //---------------------------------------------
        // Clean up the sensitive fields and insert
        // any radio promos
        //
        $n = count($records);
        for( $i = 0; $i < $n; $i++ )
        {
            $R =& $records[$i];
            $this->_clean_rec($R);
            
            CCUpload::EnsureFiles($records[$i],true);
            if( $insert_promos )
            {
                if( !$i || ($i % $promo_gap == 0) )
                {
                    $p = $promos[ $promo++ % $promo_count ];
                    $this->_clean_rec($p);
                    CCUpload::EnsureFiles($p,true);
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

                CCEvents::Invoke( CC_EVENT_API_QUERY_FORMAT, 
                                    array( &$records, $args, &$results, &$results_mime ) );

                return array( $results, $results_mime );
            }
        } // end switch

        return array( &$records, '' );
    }

    function _clean_rec(&$R)
    {
        $fields = array( 'user_email', 'user_password', 'user_last_known_ip' );
        foreach( $fields as $f )
            if( isset($R[$f]) ) unset($R[$f]);
    }


    /**
     * @access private
     */
    function _resort_records(&$records,&$sort_order)
    {
        if( !empty($sort_order) )
        {
            $sorted = array();
            $count = count($records);
            for( $i = 0; $i < $count; $i++ )
            {
                $sorted[ $sort_order[ $records[$i]['upload_id'] ] ] = $records[$i];
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
               array(  'label'      => 'Limit Queries',
                       'form_tip'   => 'Limit the number of records returned from api/query (0
                                        or blank means unlimited - HINT: that\'s a bad idea)',
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

} // end of class CCQuery


?>
