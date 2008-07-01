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

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

class CCPoolGeneric
{
    function LocalSearch($pool_id,$text,$type)
    {
        global $CC_GLOBALS;

        $text = CCUtil::StripText( urldecode($text) );
        if( empty($text) )
            return( array() );

        require_once('cchost_lib/cc-query.php');
        $query = new CCQuery();
        $dv = empty($CC_GLOBALS['use_text_index']) ? 'pool_item_search_gen' : 'pool_item_search';
        parse_str('limit=250&format=php&sort=&datasource=pool_items&dataview='.$dv,$q);
        $sql['where'] = 'pool_item_pool =' . $pool_id;
        $sql['match'] = $text;
        list( $items ) = $query->QuerySQL($q,$sql);
        return($items);
    }

}

?>