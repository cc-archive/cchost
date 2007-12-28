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

require_once('cclib/cc-feed.php');

/**
* RSS Feed generator 
*/
class CCFeedsAtom
{
    function OnApiQueryFormat( &$records, $args, &$result, &$result_mime )
    {
        if( $args['format'] != 'atom' )
            return;

        $targs['channel_title'] = cc_feed_title($args);
        $qstring = $args['queryObj']->SerializeArgs($args);
        $targs['feed_url'] = /* what's the difference again?? */
        $targs['raw_feed_url'] = htmlentities(url_args(ccl('api','query'),$qstring));
        $targs['atom-pub-date'] = CCUtil::FormatDate(CC_RFC3339_FORMAT,time());

        $k = array_keys($records);
        $c = count($k);
        for( $i = 0; $i < $c; $i++ )
        {
            $R =& $records[$k[$i]];
            $R['upload_description_plain'] = cc_feed_encode($R['upload_description_plain']);
            $R['upload_name']              = cc_feed_encode($R['upload_name']);
            $R['user_real_name']           = cc_feed_encode($R['user_real_name']);
        }

        $targs['records'] =& $records;

        require_once('cclib/cc-template.php');

        $skin = new CCSkin('atom_10.php',false);
        header("Content-type: text/xml; charset=" . CC_ENCODING); 
        $skin->SetAllAndPrint($targs,false);
        exit;
    }

    function OnRenderPage(&$page)
    {
        $qstring = $page->GetPageArg('qstring');
        if( empty($qstring) )
            return;
        parse_str($qstring,$args);
        if( !empty($args['datasource']) && ($args['datasource'] != 'uploads') )
            return;
        $feed_url = url_args( ccl('api','query'), $qstring . '&f=atom&t=atom_10');
        if( empty($args['title']) )
            $help = 'ATOM feed';
        else
            $help = $args['title'];
        $link_text = '<img src="' . ccd('ccskins','shared','images','feed-atom16x16.png') . '" title="[ Atom 1.0 ]" /> ' . $help;
        CCPage::AddLink( 'feed_links', 'alternate', 'application/atom+xml', $feed_url, $help . ' [Atom]', $link_text, 'feed_atom' );
    }

}


?>
