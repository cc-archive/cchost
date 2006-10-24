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
* @package cchost
* @subpackage feature
*/
if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,          array( 'CCArchive' , 'OnMapUrls') );

/**
*
*
*/
class CCArchive
{
    function Archive($type='',$arg1='',$tags='')
    {
        $trail = array();
        $trail[] = array( 'url' => ccl(), 'text' => _('Home') );
        $trail[] = array( 'url' => ccl('archive'), 'text' => _('Archive') );
        if( $type == 'month' )
        {
            preg_match( '/^(2[0-9]{3}-(?:01|02|03|04|05|06|07|08|09|10|11|12))$/',$arg1,$m);
            if( !empty($m[1]) )
            {
                $time = strtotime($m[1] . '-01');
                $tstr = date('F Y',$time);
                $trail[] = array( 'url' => ccl('archive','month',$arg1), 'text' => $arg1 );
                if( !empty($tags) )
                    $trail[] = array( 'url' => ccl('archive','month',$arg1,$tags), 'text' => $tags);

                CCPage::SetTitle( sprintf(_('Archive for %s'),$tstr) . ' [BETA]' );
                $month = $m[1];
                $where = "SUBSTRING(upload_date,1,7) = '${m[1]}'";
                CCUpload::ListMultipleFiles($where,$tags,'all');
                CCPage::AddBreadCrumbs($trail);
                return;
            }
        }

        CCPage::AddBreadCrumbs($trail);

        $sql =<<<END
            SELECT DATE_FORMAT( upload_date, '%M %Y' ) txt, 
                   SUBSTRING( upload_date, 1, 7 ) mo,
                   COUNT(*) cnt
            FROM cc_tbl_uploads
            GROUP BY mo
            ORDER BY mo DESC 
END;
        $dates = CCDatabase::QueryRows($sql);

        CCPage::SetTitle( _('Monthly Archive') . ' [BETA]' );
        $html = '<ul>';
        foreach( $dates as $date )
        {
            $url = ccl('archive','month',$date['mo']);
            $html .= "<li><a href=\"$url\">{$date['txt']}</a> ({$date['cnt']})</li>";
        }
        $html .= '</ul>';

        CCPage::AddPrompt('body_text',$html);
    }


    /**
    * Event handler for {@link CC_EVENT_MAP_URLS}
    *
    * @see CCEvents::MapUrl()
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('archive'), array('CCArchive','Archive'), CC_DONT_CARE_LOGGED_IN,
               ccs(__FILE__), '[month/{YYYY-MM}][/tags])', _('Display upload archive for a specific month'),
            CC_AG_UPLOADS );
    }

}



?>