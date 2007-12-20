<?


if( !defined('IN_CC_HOST') )
    die( 'Welcome to ccHost' );


function cc_filter_std(&$records,&$dataview_info)
{
    global $CC_GLOBALS;
    $c = count($records);
    $k = array_keys($records);

    foreach( array( CC_EVENT_FILTER_UPLOAD_USER_TAGS, CC_EVENT_FILTER_REMIXES_SHORT, CC_EVENT_FILTER_FILES,
                    CC_EVENT_FILTER_UPLOAD_TAGS, CC_EVENT_FILTER_REMIXES_FULL, CC_EVENT_FILTER_EXTRA,
                    CC_EVENT_FILTER_RATINGS_STARS, CC_EVENT_FILTER_DOWNLOAD_URL,
                    CC_EVENT_FILTER_UPLOAD_MENU, ) as $e )
    {
        if( !in_array( $e, $dataview_info['e'] ) )
            continue;

        for( $i = 0; $i < $c; $i++ )
        {
            $R =& $records[$k[$i]];

            switch( $e )
            {
                case CC_EVENT_FILTER_FILES:
                {
                    $sql = 'SELECT * FROM cc_tbl_files where file_upload = ' . $R['upload_id'];
                    $R['files'] = CCDatabase::QueryRows($sql);
                    $fk = array_keys($R['files']);
                    for( $fi = 0; $fi < count($fk); $fi++ )
                    {
                        $F =& $R['files'][$fk[$fi]];
                        $F['file_extra'] = unserialize($F['file_extra']);
                        $F['file_format_info'] = unserialize($F['file_format_info']);

                        if( $R['upload_contest'] )
                        {
                            // todo: this shouldn't be here
                            if( empty($CC_GLOBALS['contests']) )
                                cc_fill_contests();

                            $F['download_url'] = ccd($CC_GLOBALS['contest-upload-root'],
                                    $CC_GLOBALS['contests'][$R['upload_contest']],$R['user_name'],$F['file_name']);
                        }
                        else
                        {
                            $F['download_url'] = ccd($CC_GLOBALS['user-upload-root'],$R['user_name'],$F['file_name']);
                        }
                        $fs = $F['file_filesize'];
                        if( $fs )
                        {
                            $F['file_rawsize'] = $fs;
                            if( $fs > CC_1MG )
                                $fs = number_format($fs/CC_1MG,2) . 'MB';
                            else
                                $fs = number_format($fs/1024) . 'KB';
                            $F['file_filesize'] = " ($fs)";
                        }
                    }
                    break;
                }

                case CC_EVENT_FILTER_EXTRA:
                {
                    if( is_string($R['upload_extra']) )
                        $R['upload_extra'] = unserialize($R['upload_extra']);
                    break;
                }

                case CC_EVENT_FILTER_DOWNLOAD_URL:
                {
                    //CCDebug::PrintVar($R);
                    if( $R['upload_contest'] )
                    {
                        // todo: this shouldn't be here
                        if( empty($CC_GLOBALS['contests']) )
                            cc_fill_contests();
                        $R['download_url'] = ccd($CC_GLOBALS['contests'][$R['upload_contest']],$R['user_name'],$R['files'][0]['file_name']);
                    }
                    else
                    {
                        $R['download_url'] = ccd($CC_GLOBALS['user-upload-root'],$R['user_name'],$R['files'][0]['file_name']);
                    }
                    break;
                }

                case CC_EVENT_FILTER_UPLOAD_TAGS:
                {
                    require_once('cclib/cc-tags.inc');
                    $tags = CCTag::TagSplit($R['upload_tags']);
                    $baseurl = ccl('tags') . '/';
                    foreach( $tags as $tag )
                        $R['upload_taglinks'][] = array( 'tagurl' => $baseurl . $tag, 'tag' => $tag );
                    break;
                }

                case CC_EVENT_FILTER_UPLOAD_USER_TAGS:
                {
                    if( is_string($R['upload_extra']) )
                        $R['upload_extra'] = unserialize($R['upload_extra']);

                    require_once('cclib/cc-tags.inc');
                    $tags = CCTag::TagSplit($R['upload_extra']['ccud']);
                    $tags = array_merge($tags,CCTag::TagSplit($R['upload_extra']['usertags']));
                    $baseurl = ccl('tags') . '/';
                    foreach( $tags as $tag )
                        $R['usertag_links'][] = array( 'tagurl' => $baseurl . $tag, 'tag' => $tag );
                    break;
                }

                case CC_EVENT_FILTER_RATINGS_STARS:
                {
                    if( $R['ratings_enabled'] && !$R['thumbs_up'] )
                    {
                        $average = $R['upload_score'] / 100;
                        $count = $R['upload_num_scores'];
                        $stars = floor($average);
                        $half = ($R['upload_score'] % 100) > 25;
                        for( $ri = 0; $ri < $stars; $ri++ )
                            $R['ratings'][] = 'full';

                        if( $half )
                        {
                            $R['ratings'][] = 'half';
                            $ri++;
                        }
                        
                        for( ; $ri < 5; $ri++ )
                            $R['ratings'][] = 'empty';

                        $R['ratings_score'] = number_format($average,2) . '/' . $count;
                    }
                    break;
                }

                case CC_EVENT_FILTER_REMIXES_FULL:
                {
                    if( $dataview_info['queryObj']->args['datasource'] == 'pool_items' )
                    {
                        $query = new CCQuery();
                        $args = $query->ProcessAdminArgs('datasource=pool_items&dataview=links_by&f=php&remixes=' . $R['pool_item_id']);
                        list( $R['remix_children'] ) = $query->Query($args);
                        if( !empty($R['remix_children']) && (count($R['remix_children']) > 14) )
                            $R['children_overflow'] = true;
                    }
                    else
                    {
                        require_once('cclib/cc-query.php');
                        $query = new CCQuery();
                        $args = $query->ProcessAdminArgs('dataview=links_by_chop&f=php&sources=' . $R['upload_id']);
                        list( $R['remix_parents'] ) = $query->Query($args);
                        $query = new CCQuery();
                        $args = $query->ProcessAdminArgs('dataview=links_by_pool&f=php&sort=&datasource=pools&sources=' . $R['upload_id']);
                        list( $pool_parents ) = $query->Query($args);
                        if( $pool_parents )
                            if( empty($R['remix_parents']) )
                                $R['remix_parents'] = $pool_parents;
                            else
                                $R['remix_parents'] = array_merge( $R['remix_parents'],  $pool_parents );
                        if( !empty($R['remix_parents']) && (count($R['remix_parents']) > 14) )
                            $R['parents_overflow'] = true;
                        $query = new CCQuery();
                        $args = $query->ProcessAdminArgs('dataview=links_by_chop&f=php&remixes=' . $R['upload_id']);
                        list( $R['remix_children'] ) = $query->Query($args);
                        if( !empty($R['remix_children']) && (count($R['remix_children']) > 14) )
                            $R['children_overflow'] = true;
                    }
                    break;
                }

                case CC_EVENT_FILTER_REMIXES_SHORT:
                {
                    if( !empty($R['upload_num_sources']) )
                    {
                        $query = new CCQuery();
                        $q = 'dataview=links_by_chop&f=php&limit=4&sources=' . $R['upload_id'];
                        $args = $query->ProcessAdminArgs($q);
                        list( $R['remix_parents'] ) = $query->Query($args);
                    }

                    if( !empty($R['upload_num_pool_sources']) )
                    {
                        if( !empty($R['remix_parents']) && (count($R['remix_parents']) < 3) )
                        {
                            $count = 4 - count($R['remix_parents']);
                            $q = 'dataview=links_by_pool&f=php&limit=' . $count . '&sort=&datasource=pools&sources=' . $R['upload_id'];
                            $query = new CCQuery();
                            $args = $query->ProcessAdminArgs($q);
                            list( $pool_parents ) = $query->Query($args);
                            if( $pool_parents )
                                if( empty($R['remix_parents']) )
                                    $R['remix_parents'] = $pool_parents;
                                else
                                    $R['remix_parents'] = array_merge( $R['remix_parents'],  $pool_parents );
                        }
                    }

                    if( !empty($R['remix_parents']) && (count($R['remix_parents']) > 3) )
                    {
                        $R['more_parents_link'] = $R['file_page_url'];
                        unset($R['remix_parents'][3]);
                    }

                    if( !empty($R['upload_num_remixes']) )
                    {
                        $query = new CCQuery();
                        $q = 'dataview=links_by_chop&f=php&remixes=' . $R['upload_id'];
                        $q = 'dataview=links_by_chop&f=php&limit=4&remixes=' . $R['upload_id'];
                        $args = $query->ProcessAdminArgs($q);
                        list( $R['remix_children'] ) = $query->Query($args);
                        if( !empty($R['remix_children']) && (count($R['remix_children']) > 3) )
                        {
                            $R['more_children_link'] = $R['file_page_url'];
                            unset($R['remix_children'][3]);
                        }
                    }
                    break;
                }

                case CC_EVENT_FILTER_UPLOAD_MENU:
                {
                    // note: this is 
                    $allmenuitems = array();
                    $r = array( &$allmenuitems, &$R );
                    CCEvents::Invoke(CC_EVENT_UPLOAD_MENU, $r );

                    // sort the results
                    
                    uasort($allmenuitems ,'cc_weight_sorter');

                    // filter the results based on access permissions
                    require_once('cclib/cc-menu.php');
                    $mask = CCMenu::GetAccessMask();

                    $menu = array();
                    $count = count($allmenuitems);
                    $keys = array_keys($allmenuitems);
                    $grouped_menu = array();
                    for( $i = 0; $i < $count; $i++ )
                    {
                        $key    = $keys[$i];
                        $item   =& $allmenuitems[$key];
                        $access = $item['access'];
                        if( !($access & CC_DISABLED_MENU_ITEM) && (($access & $mask) != 0) )
                        {
                            $grouped_menu[$item['group_name']][$key] = $item;
                        }
                    }
                    $R['local_menu'] =& $grouped_menu;
                }
            } // end switch on event


        } // for each record

        $dataview_info['e'] = array_diff( $dataview_info['e'], array( $e ) );

    } // foreach event sent in

}

function cc_fill_contests()
{
    global $CC_GLOBALS;

    $sql = 'SELECT contest_id, contest_short_name FROM cc_tbl_contests'; 
    $crows = CCDatabase::QueryRows($sql);
    foreach( $crows as $crow )
        $CC_GLOBALS['contests'][ $crow['contest_id'] ] = $crow['contest_short_name'];
}

?>