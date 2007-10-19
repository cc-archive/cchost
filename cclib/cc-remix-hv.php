<?php
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
* Module for handling high volume Remix 
*
* @package cchost
* @subpackage upload
*/
if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

class CCRemixHV
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
        $rows = array ( &$row );
        $this->GetRemixHistory( $rows, empty($CC_GLOBALS['works_page']) ? 0 : CC_MAX_SHORT_REMIX_DISPLAY);
    }

    function GetRemixHistory( &$rows, $max )
    {
        global $CC_GLOBALS;

        $fhome = ccl() . 'files/';
        $phome = ccl() . 'people/';
        $limit = empty($max) ? '' : 'LIMIT ' . $max;

        $select =<<<END
                SELECT upload_name, user_real_name,
                       CONCAT( '$fhome', user_name, '/', upload_id ) as file_page_url,
                       CONCAT( '$phome', user_name ) as artist_page_url
                FROM cc_tbl_uploads
                LEFT OUTER JOIN cc_tbl_user j1 ON upload_user = user_id
END;

        $k = array_keys($rows);
        $c = count($k);
        for( $i = 0; $i < $c; $i++ )
        {
            $row =& $rows[$k[$i]];

            if( !empty($row['upload_num_sources']) )
            {
                $upload_id = $row['upload_id'];

                $sql =<<<END
                    $select
                    LEFT OUTER JOIN cc_tbl_tree j4 ON upload_id = j4.tree_parent  
                    WHERE tree_child = '$upload_id'
                    ORDER BY upload_date DESC
                    $limit
END;
                $parents = CCDatabase::QueryRows($sql);
                CCRemixHV::_mark_row($row,'has_parents','remix_parents', $parents, 'more_parents_link',$max);
            }


            if( !empty($row['upload_num_remixes']) )
            {
                $upload_id = $row['upload_id'];

                $sql =<<<END
                    $select
                    LEFT OUTER JOIN cc_tbl_tree j4 ON upload_id = j4.tree_child
                    WHERE tree_parent = '$upload_id'
                    $limit
END;

                $children = CCDatabase::QueryRows($sql);
                CCRemixHV::_mark_row($row,'has_children','remix_children', $children,'more_children_link',$max);
            }
        }

    }

    /**
    * @access private
    */
    function _mark_row(&$row,$hasflag,$elemname,&$branches,$more_name,$max)
    {
        if( empty($branches) ) 
            return false;

        $row[$hasflag] = true;
        if( empty($row[$elemname]) )
            $row[$elemname] = $branches;
        else
            $row[$elemname] = array_merge( $row[$elemname], $branches );

        if( $max && (count($branches) == $max) )
            $row[$more_name] = $row['file_page_url'];

        return(true);
    }

}


?>
