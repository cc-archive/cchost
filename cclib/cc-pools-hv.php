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
* Module for high volume (frequent) events
*
* @package cchost
* @subpackage admin
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to ccHost');

/**
*/
class CCPoolHV
{

    /**
    * Event handler for {@link CC_EVENT_UPLOAD_LISTING}
    *
    * Final chance to massage a record before being displayed in a list
    * 
    * @param array &$row Record to massage with extra display information
    */
    function OnUploadListing( &$row )
    {
        $rows = array( &$row );
        $this->GetPoolHistory( $rows, empty($CC_GLOBALS['works_page']) ? CC_MAX_SHORT_REMIX_DISPLAY : 0 );
    }

    function GetPoolHistory( &$rows, $max )
    {
        CCDebug::StackTrace();

        global $CC_GLOBALS;

        $fhome = ccl() . 'pools/item/';
        $phome = ccl() . 'pools/pool/';

        $select =<<<END
            SELECT pool_item_name as upload_name,
                   pool_item_artist as user_real_name,
                   CONCAT('$fhome', pool_item_id) as file_page_url,
                   CONCAT('$fhome', pool_item_id) as artist_page_url,
                   CONCAT('$phome', pool_item_pool) as pool_item_pool_url 
                FROM cc_tbl_pool_item 
END;

        require_once('cclib/cc-remix-hv.php');

        $k = array_keys($rows);
        $c = count($k);
        for( $i = 0; $i < $c; $i++ )
        {
            $row =& $rows[$k[$i]];

            if( !empty($row['upload_num_pool_sources']) )
            {
                $onum = intval($row['upload_num_sources']);
                if( !$max || ( $onum < $max) ) 
                {
                    if( $max )
                        $limit = 'LIMIT ' . ($max - $onum);
                    else
                        $limit = '';

                    $upload_id = $row['upload_id'];

                    $sql =<<<END
                        $select
                        LEFT OUTER JOIN cc_tbl_pool_tree j3 ON pool_item_id = pool_tree_pool_parent  
                        WHERE pool_tree_child = $upload_id
                        $limit
END;

                    $parents = CCDatabase::QueryRows($sql);
                    CCRemixHV::_mark_row($row,'has_parents','remix_parents',$parents,'more_parents_link',$max);
                }
            }

            // This field is NOT sync'd (!!) and can't be relied on... (probably a bug)

            //if( empty($row['upload_num_pool_remixes']) )
            //    continue;
    
            $onum = intval($row['upload_num_remixes']);
            if( !$max || ( $onum < $max ) )
            {
                if( $max )
                    $limit = 'LIMIT ' . ($max - $onum);
                else
                    $limit = '';

                $upload_id = $row['upload_id'];

                $sql =<<<END
                    $select
                    LEFT OUTER JOIN cc_tbl_pool_tree j3 ON pool_item_id = pool_tree_pool_child  
                    WHERE j3.pool_tree_parent = '$upload_id' AND (pool_item_approved > 0)  
                    $limit
END;

                $children = CCDatabase::QueryRows($sql);
                CCRemixHV::_mark_row($row,'has_children','remix_children',$children,'more_children_link',$max);
            }
        }
    }


}

?>
