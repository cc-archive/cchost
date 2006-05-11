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
* Module for handling remix tables
*
* @package cchost
* @subpackage upload
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
 * Base class for remix fork database table
 *
 * This wrapper is a little different in that it is used
 * to wrap more than one mySQL table: the 'regular' remix
 * tree as well as the sample pool tree.
 */
class CCRemixTree extends CCTable
{
    /**#@+
    * @access private
    */
    var $_bind_to_query;
    var $_uploads;
    /**#@-*/

    /**
    * Constructor
    *
    * Callers can bind to this table as a parent looking for children
    * or visa versa
    *
    * @see CCRemixes
    * @see CCRemixSources
    * @param string $bind_to_upload The name of key column
    * @param string $bind_to_query The name of column that will be returned by queries
    * @param string $table_name Physical table to wrap
    */
    function CCRemixTree( $bind_to_upload, $bind_to_query, $table_name = 'cc_tbl_tree')
    {
        $this->CCTable($table_name,$bind_to_upload);
        $this->_bind_to_query = $bind_to_query;
    }

    /**
    * Use by derived classes to return _bind_to_query results
    *
    * @param integer $upload_id Upload to get remix relatives
    * @param boolean $limit true: Limit results by {@link CC_MAX_SHORT_REMIX_DISPLAY}
    * @return array &$records Full blown records for remixes
    */
    function & _get_relatives($upload_id, $limit)
    {
        if( !isset($this->_uploads) )
        {
            $this->_uploads = new CCUploads();
        }

        $joinid = $this->_uploads->AddJoin($this,'upload_id');
        $where = $joinid . '.' . $this->_bind_to_query . " = '$upload_id'";
        if( $limit )
            $this->_uploads->SetOffsetAndLimit( 0, CC_MAX_SHORT_REMIX_DISPLAY );
        //$sql = $this->_uploads->_get_select($where);
        //CCDebug::PrintVar($sql);
        $rows = $this->_uploads->QueryRows($where);
        $this->_uploads->RemoveJoin($joinid);
        $records =& $this->_uploads->GetRecordsFromRows($rows);
        return $records;
    }

    /**
    * @access private
    */
    function & _get_user_stat($user_id,$count_only)
    {
        if( $count_only )
            $cols = 'COUNT(*)';
        else
            $cols = 'relatives.upload_id';

        // yea, I'm not 100% sure this will cover enough cases
        $extra_where = CCUser::IsAdmin() ? '' : 
            'AND (relatives.upload_banned < 1) AND (relatives.upload_published || relatives.upload_contest)';

        $sql =<<<END
            SELECT $cols FROM  $this->_table_name tree
            JOIN cc_tbl_uploads me        ON me.upload_id        = tree.$this->_key_field
            JOIN cc_tbl_uploads relatives ON relatives.upload_id = tree.$this->_bind_to_query
            WHERE me.upload_user = $user_id 
            $extra_where
END;

        if( $count_only )
        {
            $count = CCDataBase::QueryItem($sql);
            return $count;
        }

        $ids = CCDatabase::QueryItems($sql);
        if( !empty($ids) )
        {
            $uploads =& CCUploads::GetTable();
            $uploads->SetOrder('upload_date','DESC');
            $records =& $uploads->GetRecordsFromKeys($ids);
            $uploads->SetOrder('');
            return $records;
        }
        $e = array();
        return($e);
    }

}

/**
 * Virtual table class to represent the remix sources of a remix
 *
 * @access public
 */
class CCRemixSources extends CCRemixTree
{
    function CCRemixSources()
    {
        $this->CCRemixTree('tree_parent','tree_child');
    }

    /**
    * Return the remix sources for a given record
    *
    * NOTE: This method is, er, performance intensive. See {@link CCRemix::OnUploadListing()} for
    * an example of an optimized version.
    * 
    * The $limit_by_works_page parameter is (unfortunately) a flip-negative flag
    * that will limit the number of records returned based on the following 
    * conditions:
    *
    *   If $limit_by_works_page is <i>true</i> AND $record['works_page'] is <i>false</i>
    *   then the number of records returned will be max out at {@link CC_MAX_SHORT_REMIX_DISPLAY}
    *
    * This flag is used when we are doing an upload listing and only have room for a few
    * remix relatives.
    *
    * @param array $record Record to get remixes of
    * @param boolean $limit_by_works_page (See method comments)
    */
    function & GetSources($record,$limit_by_works_page=true)
    {
        $do_limit = $limit_by_works_page ? empty($record['works_page']) : false;
        $s =& $this->_get_relatives($record['upload_id'],$do_limit);
        return $s;
    }

    function & GetRemixesOf($user_id,$count_only=false)
    {
        $rof =& $this->_get_user_stat($user_id,$count_only);
        return $rof;
    }

    /**
    * Returns static singleton of table wrapper.
    * 
    * Use this method instead of the constructor to get
    * an instance of this class.
    * 
    * @returns object $table An instance of this table
    */
    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCRemixSources();
        return $_table;
    }
}

/**
 * Virtual table class to represent the remixes of a given source
 *
 * @access public
 */
class CCRemixes extends CCRemixTree
{
    function CCRemixes()
    {
        $this->CCRemixTree('tree_child','tree_parent');
    }

    /**
    * Return the remixes for a given record
    *
    * NOTE: This method is, er, performance intensive. See {@link CCRemix::OnUploadListing()} for
    * an example of an optimized version.
    * 
    * The $limit_by_works_page parameter is (unfortunately) a flip-negative flag
    * that will limit the number of records returned based on the following 
    * conditions:
    *
    *   If $limit_by_works_page is <i>true</i> AND $record['works_page'] is <i>false</i>
    *   then the number of records returned will be max out at {@link CC_MAX_SHORT_REMIX_DISPLAY}
    *
    * This flag is used when we are doing an upload listing and only have room for a few
    * remix relatives.
    *
    * @param array $record Record to get remixes of
    * @param boolean $limit_by_works_page (See method comments)
    */
    function & GetRemixes($record,$limit_by_works_page=true)
    {
        $do_limit = $limit_by_works_page ? empty($record['works_page']) : false;
        $r =& $this->_get_relatives($record['upload_id'],$do_limit);
        return $r;
    }

    function & GetRemixedBy($user_id,$count_only=false)
    {
        $rby =& $this->_get_user_stat($user_id,$count_only);
        return $rby;
    }


    /**
    * Returns static singleton of table wrapper.
    * 
    * Use this method instead of the constructor to get
    * an instance of this class.
    * 
    * @returns object $table An instance of this table
    */
    function & GetTable()
    {
        static $_table;
        if( !isset($_table) )
            $_table = new CCRemixes();
        return $_table;
    }
}


?>