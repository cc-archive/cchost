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
* $Id: mixter.php 3245 2006-04-06 07:21:19Z fourstones $
*
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

define( 'CC_TS_POPULAR', 1 );
define( 'CC_TS_ALPHA',   2 );
define( 'CC_TS_DEFAULT', CC_TS_ALPHA );

define( 'CC_TS_MINTAGS', 3 );

class CCSampleBrowserAPI
{
    function SamplesSearch()
    {
        if( empty($_GET['term']) )
        {
            print('<span class="sb_msg">' . 
                   _('Enter a search phrase first...') . 
                  '</span>');
            exit;
        }

        $term = $_GET['term'];
        $where = "LOWER(CONCAT(upload_description,upload_tags,user_real_name,user_name,upload_name)) LIKE '%$term%'";

        $uploads =& CCUploads::GetTable();
        $uploads->SetTagFilter('sample');
        $recs = $uploads->GetRecords($where);
        
        $this->_list_records($recs,"\"$term\"", _('Search phrase'));
    }

    function SamplesStream($file_id_with_m3u)
    {
        $file_id = str_replace('.m3u', '', $file_id_with_m3u);
        $files =& CCFiles::GetTable();
        $upload_id = $files->QueryItemFromKey('file_upload', $file_id);
        if( !empty($upload_id) )
        {
            $uploads =& CCUploads::GetTable();
            $record = $uploads->GetRecordFromKey($upload_id);
            if( !empty($record) )
            {
                $F =& $record['files'];
                $count = count($F);
                for( $i = 0; $i < $count; $i++ )
                {
                    if( $F[$i]['file_id'] == $file_id )
                    {
                        header("Content-type: audio/x-mpegurl");
                        print( $F[$i]['download_url'] . "\n" );
                        break;
                    }
                }
            }
        }
        // there's no point in doing error messages since this is
        // fed to media player, I guess we could return 404...
        exit;
    }

    function _default_msg()
    {
        return '<span class="sb_msg">' . 
                  _('Select a combination of tags from the list or enter a phrase and hit \'Go\' ...') . 
               '</span>';
    }

    function SamplesList()
    {
        if( empty($_GET['tags']) )
        {
            print( $this->_default_msg() );
            exit;
        }

        $tags = $_GET['tags'];

        if( !empty($_GET['tf']) && in_array( $_GET['tf'], array( 'any', 'all' ) ) )
            $match = $_GET['tf'];
        else
            $match = 'any';


        $uploads =& CCUploads::GetTable();
        $uploads->SetDefaultFilter(true,true); // filter out banned and hidden packs
        $uploads->SetSort( 'upload_date', 'DESC' );
        $uploads->SetTagFilter($tags,$match);


        // this is the only way to AND the 'sample' tag
        // and OR the rest of the them...

        if( !empty($_GET['bpm']) && ($_GET['bpm'] != '-') )
        {
            $bpm = $_GET['bpm'];
            $bpm_where = " AND (upload_tags REGEXP '(^| |,)(bpm_{$bpm})(,|\$)' )";
        }
        else
        {
            $bpm = '';
            $bpm_where = '';
        }

        $where = "(upload_tags REGEXP '(^| |,)(sample)(,|\$)' )" . $bpm_where;

        $recs =& $uploads->GetRecords($where);

        if( strpos( $tags, ',' ) !== false )
             $term = str_replace(',',', ',$tags) . ' (' . $match . ')';
        else
            $term = $tags;
        if( $bpm )
            $term .= " ($bpm BPM)";

        $this->_list_records($recs,$term, _('Tags'));
    }

    function _list_records(&$recs,$term,$term_caption)
    {
        global $CC_GLOBALS;

        // phpTal does NOT like the hyphen in "media-type" so we have to mark
        // each file record as 'is_audio' manually

        $count = count($recs);
        for( $i = 0; $i < $count; $i++ )
        {
            $F =& $recs[$i]['files'];
            $fcount = count($F);
            for( $n = 0; $n < $fcount; $n++ )
            {
                $F[$n]['is_audio'] = !empty($F[$n]['file_format_info']['media-type']) && 
                                   ( $F[$n]['file_format_info']['media-type'] == 'audio' );
            }
            $recs[$i]['odd'] = $i & 1 ? 'odd' : 'even';
        }

        $args = $CC_GLOBALS;
        $args['records']         =& $recs;
        $args['results_caption'] = sprintf( 'returns %d results', count($args['records']) );
        $args['term']            = $term;
        $args['filter']          = $match;
        $args['browser_macro']   = 'browser_list';
        $args['term_caption']    = $term_caption;

        if( empty($args['records']) )
        {
            print('<span class="sb_msg">' .
                  sprintf( _('Could not find any matches for  "%s"...'), $term ) .
                  '</span>');
            exit;
        }

        //CCDebug::PrintVar($args['records'][0],false);

        $template = new CCTemplate( $CC_GLOBALS['files-root'] . 'sample_browser.xml' );
        $html = $template->SetAllAndParse($args);
        print($html);
        exit;
    }

    function SamplesBrowse()
    {
        global $CC_GLOBALS;

        $args = $CC_GLOBALS;
        list( $tags, $bpms ) = $this->_get_tags();

        $args['tags']           =& $tags;
        $args['bpms']           =& $bpms;
        $args['tag_sorts']      =& $this->_get_tag_sorts();
        $args['tag_filters']    =& $this->_get_tag_filters();
        $args['tag_limiters']   =& $this->_get_tag_limiters();
        $args['browser_macro']  = 'browser_page';
        $args['picker_caption'] = _('Search using tags:');
        $args['search_caption'] = _('Search for phrase:');
        $args['bpm_caption']    = _('Limit to BPM:');
        $args['search_text']    = _('Go');
        $args['default_msg']    = $this->_default_msg();

        $template = new CCTemplate( $CC_GLOBALS['files-root'] . 'sample_browser.xml' );
        $html = $template->SetAllAndParse($args);
        CCPage::AddPrompt('body_text',$html);
        CCPage::AddScriptBlock('ajax_block');
    }

    function _get_tags()
    {
        $uploads =& CCUploads::GetTable();
        $uploads->SetDefaultFilter(true,true); // filter out banned and hidden packs
        $uploads->SetTagFilter( 'sample' );
        $rows = $uploads->QueryRows('','upload_extra');
        $count = count($rows);
        $tagstrs = array();
        $bpms = array();
        if( empty($_GET['bpm']) )
            $selbpm = '-';
        else
            $selbpm = $_GET['bpm'];
        for( $i = 0; $i < $count; $i++ )
        {
            $ex = unserialize($rows[$i]['upload_extra']);
            if( !empty($ex['usertags']) )
               $tagstrs[] = $ex['usertags'];
            if( preg_match( '/bpm_([0-9][^,$]+)/', $ex['ccud'], $m ) )
            {
                $bpms[ $m[1] ] = array( 'bpm' => $m[1],
                                        'selected' => $selbpm == $m[1] );
            }
        }
        ksort($bpms);
        $bpm_head['-']   =  array( 'bpm' => '-',         'selected' => $selbpm == '-' );
        $bpm_head['b60']  = array( 'bpm' => 'below_60',  'selected' => $selbpm == 'below_60' );
        $bpm_tail['a180'] = array( 'bpm' => 'above_180', 'selected' => $selbpm == 'above_180' );
        $bpm_final = array_merge( $bpm_head, $bpms, $bpm_tail );

        //CCDebug::Enable(true);

        $all_tags = strtolower( join(',', $tagstrs ) );
        $all_tags = preg_split(CC_TAG_SPLITTER,$all_tags);

        if( !empty( $_GET['ts'] ) )
            $ts = intval($_GET['ts']);
        if( empty($ts) )
            $ts = CC_TS_DEFAULT;

        $tag_counts = array_count_values($all_tags);
        $tag_rows = array();
        switch( $ts )
        {
            case CC_TS_POPULAR:
                foreach( $tag_counts as $tag => $count )
                {
                    $key = $count * 1000;
                    while( array_key_exists( $key, $tag_rows ) )
                        ++$key;
                    $tag_rows[$key] = array( 'tags_tag' => $tag,
                                       'tags_count' => $count );
                }
                krsort($tag_rows);
                break;

            case CC_TS_ALPHA:
                ksort($tag_counts);
                if( empty($_GET['sa']) )
                {
                    $min = CC_TS_MINTAGS;
                }
                else
                {
                    if( $_GET['sa'] == 'all' )
                        $min = 0;
                    else
                        $min = intval($_GET['sa']);
                }
                foreach( $tag_counts as $tag => $count )
                {
                    if( !$min || $count >= $min )
                    {
                        $tag_rows[] = array( 'tags_tag' => $tag,
                                             'tags_count' => $count );
                    }
                }
                break;
        }

        
        return array( &$tag_rows, &$bpm_final );
    }

    function & _get_tag_sorts()
    {
        if( empty($_GET['ts']) )
            $sel = CC_TS_DEFAULT;
        else
            $sel = intval($_GET['ts']);

        $sels = array( 
            array( 'value' => CC_TS_POPULAR,
                   'text'  => 'Sort by popular',
                    'selected' => $sel == CC_TS_POPULAR ),
            array( 'value' => CC_TS_ALPHA,
                   'text'  => 'Sort alphabetical',
                    'selected' => $sel == CC_TS_ALPHA ),
            );

        return $sels;
    }

    function & _get_tag_filters()
    {
        if( !empty($_GET['tf']) && in_array( $_GET['tf'], array( 'any', 'all' ) ) )
            $sel = $_GET['tf'];
        else
            $sel = 'any';

        $sels = array( 
            array( 'value' => 'any',
                   'text'  => _('Match any tags'),
                    'selected' => $sel == 'any' ),
            array( 'value' => 'all',
                   'text'  => _('Match all tags'),
                    'selected' => $sel == 'all' ),
            );

        return $sels;
    }

    function & _get_tag_limiters()
    {
        if( !empty($_GET['sa']) && in_array( $_GET['sa'], array( CC_TS_MINTAGS, 'all' ) ) )
            $sel = $_GET['sa'];
        else
            $sel = CC_TS_MINTAGS;

        $sels = array( 
            array( 'value' => CC_TS_MINTAGS,
                   'text'  => _('Popular only'),
                    'selected' => $sel == CC_TS_MINTAGS ),
            array( 'value' => 'all',
                   'text'  => _('Show all'),
                    'selected' => $sel == 'all' ),
            );

        return $sels;
    }
}

?>