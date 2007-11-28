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
                      CC_EVENT_FILTER_RATINGS_STARS, CC_EVENT_FILTER_DOWNLOAD_URL ) as $e )
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
                            $F['download_url'] = ccd($CC_GLOBALS['contests'][($R['upload_contest']-1)],$R['user_name'],$F['file_name']);
                        else
                            $F['download_url'] = ccd($CC_GLOBALS['user-upload-root'],$R['user_name'],$F['file_name']);
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
                        $R['download_url'] = ccd($CC_GLOBALS['contests'][($R['upload_contest']-1)],$R['user_name'],$R['file_name']);
                    else
                        $R['download_url'] = ccd($CC_GLOBALS['user-upload-root'],$R['user_name'],$R['file_name']);
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
                            $i++;
                        }
                        
                        for( ; $ri < 5; $ri++ )
                            $R['ratings'][] = 'empty';
                        
                        $R['ratings_score'] = number_format($average,2) . '/' . $count;
                    }
                    break;
                }

                case CC_EVENT_FILTER_REMIXES_FULL:
                {
                    $query = new CCQuery();
                    $q = 'dataview=links_by_chop&f=php&&sources=' . $R['upload_id'];
                    $args = $query->ProcessAdminArgs($q);
                    list( $parents ) = $query->Query($args);
                    $q = 'dataview=links_by_pool&f=php&sort=&datasource=pools&sources=' . $R['upload_id'];
                    $query = new CCQuery();
                    $args = $query->ProcessAdminArgs($q);
                    list( $pool_parents ) = $query->Query($args);
                    $R['remix_parents'] = array_merge($parents,$pool_parents);
                    if( count($R['remix_parents']) > 14 )
                        $R['parents_overflow'] = true;
                    $query = new CCQuery();
                    $q = 'dataview=links_by_chop&f=php&&remixes=' . $R['upload_id'];
                    $args = $query->ProcessAdminArgs($q);
                    list( $R['remix_children'] ) = $query->Query($args);
                    if( count($R['remix_children']) > 14 )
                        $R['children_overflow'] = true;
                    break;
                }

                case CC_EVENT_FILTER_REMIXES_SHORT:
                {
                    if( empty($R['upload_num_sources']) )
                    {
                        $parents = array();
                    }
                    else
                    {
                        $query = new CCQuery();
                        $q = 'dataview=links_by_chop&f=php&limit=4&sources=' . $R['upload_id'];
                        $args = $query->ProcessAdminArgs($q);
                        list( $parents ) = $query->Query($args);
                    }

                    if( count($parents) < 3 && !empty($R['upload_num_pool_sources']) )
                    {
                        $count = 4 - count($parents);
                        $q = 'dataview=links_by_pool&f=php&limit=' . $count . '&sort=&datasource=pools&sources=' . $R['upload_id'];
                        $query = new CCQuery();
                        $args = $query->ProcessAdminArgs($q);
                        list( $pool_parents ) = $query->Query($args);
                        $parents = array_merge($parents,$pool_parents);
                    }
                    if( count($parents) > 3 )
                    {
                        $R['more_parents_link'] = $R['file_page_url'];
                        unset($parents[3]);
                    }
                    $R['remix_parents'] = $parents;

                    if( empty($R['upload_num_remixes']) )
                    {
                        $children = array();
                    }
                    else
                    {
                        $query = new CCQuery();
                        $q = 'dataview=links_by_chop&f=php&limit=4&remixes=' . $R['upload_id'];
                        $args = $query->ProcessAdminArgs($q);
                        list( $children ) = $query->Query($args);
                        if( count($children) > 3 )
                        {
                            $R['more_children_link'] = $R['file_page_url'];
                            unset($children[3]);
                        }
                    }
                    $R['remix_children'] = $children;
                    break;
                }

            } // end switch on event


        } // for each record

        $dataview_info['e'] = array_diff( $dataview_info['e'], array( $e ) );

    } // foreach event sent in

}


?>