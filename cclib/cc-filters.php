<?


if( !defined('IN_CC_HOST') )
    die( 'Welcome to ccHost' );


function cc_filter_std(&$records,&$query_args,&$dataview_info)
{
    global $CC_GLOBALS;
    $c = count($records);
    $k = array_keys($records);

    foreach( array( CC_EVENT_FILTER_UPLOAD_USER_TAGS, CC_EVENT_FILTER_REMIXES_SHORT, 
                      CC_EVENT_FILTER_RATINGS_STARS, CC_EVENT_FILTER_DOWNLOAD_URL ) as $e )
    {
        if( !in_array( $e, $dataview_info['e'] ) )
            continue;

        for( $i = 0; $i < $c; $i++ )
        {
            $R =& $records[$k[$i]];

            switch( $e )
            {
                case CC_EVENT_FILTER_DOWNLOAD_URL:
                {
                    if( $R['upload_contest'] )
                        $R['download_url'] = ccd($CC_GLOBALS['contests'][($R['upload_contest']+1)],$R['user_name'],$R['file_name']);
                    else
                        $R['download_url'] = ccd($CC_GLOBALS['user-upload-root'],$R['user_name'],$R['file_name']);
                    break;
                }

                case CC_EVENT_FILTER_UPLOAD_USER_TAGS:
                {
                    if( is_string($R['upload_extra']) )
                        $R['upload_extra'] = unserialize($R['upload_extra']);

                    if( empty($R['upload_extra']['user_tags']) )
                    {
                        $R['user_tags'] = array();
                    }
                    else
                    {
                        require_once('cclib/cc-tags.inc');
                        $tags = CCTag::TagSplit($R['upload_extra']['user_tags']);
                        $baseurl = ccl('tags') . '/';
                        foreach( $tags as $tag )
                            $R['user_tags'][] = array( 'tagurl' => $baseurl . $tag, 'tag' => $tag );
                    }
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

                        for( $i = 0; $i < $stars; $i++ )
                            $R['ratings'][] = 'full';

                        if( $half )
                        {
                            $R['ratings'][] = 'half';
                            $i++;
                        }
                        
                        for( ; $i < 5; $i++ )
                            $record['ratings'][] = 'empty';
                        
                        $record['ratings_score'] = number_format($average,2) . '/' . $count;
                    }
                    break;
                }

                case CC_EVENT_FILTER_REMIXES_SHORT:
                {
                    $query = new CCQuery();
                    $q = 'dataview=links_by&f=php&limit=3&remixes=' . $R['upload_id'];
                    $args = $query->ProcessAdminArgs($q);
                    list( $R['remix_children'] ) = $query->Query($args);
                    CCDebug::PrintVar($R);

                    // do parents later

                    break;
                }

            } // end switch on event

        } // for each record
        
        $dataview_info['e'] = array_diff( $dataview_info['e'], array( $e ) );


    } // foreach event sent in


}


?>