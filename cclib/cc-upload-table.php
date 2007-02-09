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
* @subpackage io
*/
if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');


/**
* Wrapper for cc_tbl_uploads SQL table
*
* There are two tables that manage uploads, the CCUploads table manages 
* the meta data for the upload, CCFiles handles the actual physical
* files. (There can be multiple physical files for one upload record.)
*
* @see CCUploads::CCUploads()
*/
class CCUploads extends CCTable
{
    var $_tags;
    var $_tag_filter_type; // 'any' or 'all'
    var $_filter;

    /**
    * Constructor
    */
    function CCUploads($anon_user=false)
    {
        global $CC_SQL_DATE, $CC_CFG_ROOT;

        require_once('cclib/cc-license.php');

        $this->CCTable('cc_tbl_uploads','upload_id');

        $juser = $this->AddJoin( new CCUsers(),    'upload_user');
        $this->AddJoin( new CCLicenses(), 'upload_license');

        $this->AddExtraColumn("DATE_FORMAT(upload_date, '$CC_SQL_DATE') as upload_date_format");
        $this->AddExtraColumn("DATE_FORMAT($juser.user_registered, '$CC_SQL_DATE') as user_date_format");
        $this->AddExtraColumn("0 as works_page, upload_banned as skip_remixes, 0 as ratings_score, 0 as reviews_link");

        $this->SetDefaultFilter(true,$anon_user);
    }

    /**
     * Override default sensitity to logged in user
     *
     * Typically this class is sensitive to the current user, showing files
     * they own even if it's banned or unpublished, etc. (Admins can see 
     * everything) Use the 'anon_user' flag on the constructor to create
     * an instance assumes anonymous user which is safe to use when 
     * creating lists that might be seen by someone other than the owner
     * or admin.
    */
    function SetDefaultFilter($set,$anon_user=false)
    {
        $this->_filter = '';

        if( !$set )
            return '';

        // if the current user is admin, don't put 
        // any filters on the listings

        if( $anon_user || !CCUtil::IsHTTP() || !CCUser::IsAdmin() )
        {
            $this->_filter .= " ( ";

            if( !$anon_user && CCUser::IsLoggedIn() )
            {
                $userid = CCUser::CurrentUser();

                // let the current user see all their
                // files no matter what

                $this->_filter .= "( upload_user = $userid ) OR ";
            }

            $this->_filter .= "( upload_published > 0 AND upload_banned < 1 ) )";

        } // endif for non-admin users filters

        return $this->_filter;
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
            $_table = new CCUploads();
        return $_table;
    }

    /**
    * Limit query results to certain tags
    *
    * The 'tags' parameter can either be a comma separeated list of tags
    * or an array of array.
    *
    * The 'type' parameter determines how the tags are combined, either 
    * 'any' or 'all'
    *
    * @param mixed $tags What tags to limit the search by
    * @param string $type One of 'any' or 'all'
    */
    function SetTagFilter($tags, $type='any')
    {
        if( is_array($tags) )
            $tags = implode(', ',$tags);

        $this->_tags = $tags;
        $this->_tag_filter_type = $type;
    }

    /**
    * Convert a database 'row' to a more semantically rich 'record'
    * 
    * This method is abstract (returns $row). Derived classes
    * implement this method for shortly after a row from the database has
    * been returned to fill the row with semantically rich, runtime data.
    *
    * For a tutorial see {@tutorial cchost.pkg#rowvsrecord "row" vs. "record"}
    * 
    * @param array $row Row as retrieved from the database
    * @return array $record A 'record' that has runtime data
    */
    function & GetRecordFromRow( &$row )
    {
        if( is_string( $row['upload_extra'] ) )
            $row['upload_extra'] = unserialize($row['upload_extra']);

        $row['upload_description_html'] = CCUtil::TextToHTML($row['upload_description']);

        $row['upload_short_name'] = strlen($row['upload_name']) > 18 ? 
                                        substr($row['upload_name'],0,17) . '...' : 
                                        $row['upload_name'];

        $files =& CCFiles::GetTable();
        $row['files'] = $files->FilesForUpload($row);
        
        $baseurl = ccl('tags');
        require_once('cclib/cc-tags.php');
        $utags = CCTag::TagSplit($row['upload_tags']);
        foreach($utags as $tag)
        {
            $row['upload_taglinks'][] = array( 'tagurl' => $baseurl . '/' . $tag,
                                               'tag' => $tag );
        }  

        $utags = $row['upload_extra']['ccud'];
        if( !empty($row['upload_extra']['usertags']) )
        {
            $ut = trim( $row['upload_extra']['usertags'] );
            if( !empty($ut) )
                $utags .= ',' . $ut;
        }

        $utags = CCTag::TagSplit($utags);
        foreach($utags as $tag)
        {
            $row['usertag_links'][] = array( 'tagurl' => $baseurl . '/' . $tag,
                                             'tag' => $tag );
        }  

        CCEvents::Invoke(CC_EVENT_UPLOAD_ROW, array( &$row ));

        
        if( $row['upload_banned'] )
        {
            $row['upload_name_cls'] = 'cc_name_ban';
        }
        elseif( empty($row['upload_contest']) && !$row['upload_published'] )
        {
            $row['upload_name_cls'] = 'cc_name_hidden';
        }
        else
        {
            $row['upload_name_cls'] = '';
        }

        return $row;
    }

/*
//    function IsMediaType(&$record,$looking_for_media,$looking_for_ext='')
    {
        if( empty($record['files']) )
        {
            CCDebug::StackTrace();
            trigger_error('Invalid call to IsMediaType');
        }
        $file = $record['files'][0];
        if( empty($file['file_format_info']['media-type']) )
            return(false);
        $mt = $file['file_format_info']['media-type'];
        $ok = ($mt == $looking_for_media);
        if( $ok && $looking_for_ext )
            $ok =  $file['file_format_info']['default-ext'] == $looking_for_ext;
        return($ok);
    }
*/

    /**
    * Shortcut for getting the basic file format
    * @param array &$record Upload record
    * @param string $field Optionally specific just one field
    * @return mixed Field value or entire format info structure
    */
    function GetFormatInfo(&$record,$field='')
    {
        if( empty($record['files']) )
        {
            CCDebug::StackTrace();
            trigger_error(_('Invalid call to GetFormatInfo'));
        }
        $file = $record['files'][0];
        if( empty($file['file_format_info']) )
            return(null);
        $F = $file['file_format_info'];
        if( $field && empty($F[$field]) )
            return( null );
        if( $field )
            return( $F[$field] );
        return( $F );
    }

    /**
    * Set data into the upload record
    *
    * See {@tutorial cchost.pkg#uploadextra a tutorial} on how use this method.
    * 
    * @param mixter $id_or_row Interger upload id or upload record
    * @param string $fieldname Name of extra field
    * @param value $value Value to set into field
    */
    function SetExtraField( $id_or_row, $fieldname, $value)
    {
        if( is_array($id_or_row) )
        {
            $extra = $id_or_row['upload_extra'];
            $id = $id_or_row['upload_id'];
            if( is_string($extra) )
                $extra = unserialize($extra);
        }
        else
        {
            $id = $id_or_row;
            $row = $this->QueryKeyRow($id_or_row);
            $extra = unserialize($row['upload_extra']);
        }

        $extra[$fieldname] = $value;
        $args['upload_extra'] = serialize($extra);
        $args['upload_id'] = $id;
        $this->Update($args);
    }

    /**
    * Get data out of the upload record
    *
    * See {@tutorial cchost.pkg#uploadextra a tutorial} on how use this method.
    * 
    * @param mixter $id_or_row Interger upload id or upload record
    * @param string $fieldname Name of extra field
    */
    function GetExtraField( &$id_or_row, $fieldname )
    {
        if( is_array($id_or_row) )
        {
            $extra = $id_or_row['upload_extra'];
            if( is_string($extra) )
                $extra = unserialize($extra);
        }
        else
        {
            $row = $this->QueryKeyRow($id_or_row);
            $extra = unserialize($row['upload_extra']);
        }
        if( !empty($extra[$fieldname]) )
            return( $extra[$fieldname] );

        return( null );
    }

    /**
    * Check this record for tag
    *
    * @param mixed $tags Comma separated list or array of tags
    * @param array $record Upload record 
    * @return boolean true means tags are in record 
    */
    function InTags($tags,&$record)
    {
        return( CCTag::InTag($tags,$record['upload_tags']));
    }

    /**
    * Return tags for upload in array
    *
    * @param array $record Upload record to get tags from
    * @return array Array of tags for this record
    */
    function SplitTags(&$record)
    {
        return( CCTag::TagSplit($record['upload_tags']) );
    }

    /**
    * Overwrite parent's version to add descriptor
    * 
    * @param mixed $where Where filter for next query
    * @param string $columns Return these columns
    */
    function _get_select($where,$columns='*')
    {
        $where = $this->_where_to_string($where);
        $where = $this->_tags_to_where($where);

        if( !empty($this->_filter) )
        {
            if( empty($where) )
                $where = $this->_filter;
            else
                $where = "$where AND \n ({$this->_filter})";
        }

        return( parent::_get_select($where,$columns) );
    }

    function _tags_to_where($where)
    {
        if( !empty($this->_tags) )
        {
            if( $this->_tag_filter_type == 'any' )
            {
                $tagors = preg_replace('/[ ]?,[ ]?/','|',$this->_tags);
                $filter = " upload_tags REGEXP '(^| |,)($tagors)(,|\$)' ";
            }
            else
            {
                $tagands = array();
                require_once('cclib/cc-tags.php');
                $tagsarr = CCTag::TagSplit($this->_tags);
                foreach( $tagsarr as $tag )
                {
                    if( $tag{0} == '-' )
                    {
                        $tag = substr($tag,1);
                        $not = ' NOT ';
                    }
                    else
                    {
                        $not = '';
                    }
                    $tagands[] = "(upload_tags $not REGEXP '(^| |,)($tag)(,|\$)' )";
                }
                $filter = implode( ' AND ', $tagands );
            }
            if( empty($where) )
                $where = $filter;
            else
                $where = "$where AND ($filter)";
        }

        return $where;
    }
}


/**
* Wrapper for cc_tbl_files SQL table
*
* There are two tables that manage uploads, the CCUploads table manages 
* the meta data for the upload, CCFiles handles the actual physical
* files. (There can be multiple physical files for one upload record.)
*
* @see CCUploads::CCUploads()
*/
class CCFiles extends CCTable
{
    /**
    * Constructor -- don't use new, use GetTable() instead
    *
    * @see CCTable::GetTable()
    */
    function CCFiles()
    {
        $this->CCTable('cc_tbl_files','file_id');
        $this->SetOrder('file_order');
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
            $_table = new CCFiles();
        return( $_table );
    }

    /**
    * Return array of files records given an upload record
    *
    * @param mixed $row a row from CCUploads table
    * @returns array $rows Returns array of file records
    */
    function FilesForUpload($row)
    {
        $upload_id = is_array($row) ? $row['upload_id'] : $row;

        $where['file_upload'] = $upload_id;
        $rows = $this->QueryRows($where);
        for( $i = 0; $i < count($rows); $i++ )
        {
            $rows[$i]['file_format_info'] = unserialize($rows[$i]['file_format_info']);
            $rows[$i]['file_extra']       = unserialize($rows[$i]['file_extra']);
            $this->_format_file_size($rows[$i]);
        }

        CCEvents::Invoke(CC_EVENT_UPLOAD_FILES, array(&$row,&$rows) );

        return($rows);
    }

    /**
    * Internal goodie for formatting file sizes
    */
    function _format_file_size(&$row)
    {
        $fs = $row['file_filesize'];
        if( $fs )
        {
            $row['file_rawsize'] = $fs;
            if( $fs > CC_1MG )
                $fs = number_format($fs/CC_1MG,2) . 'MB';
            else
                $fs = number_format($fs/1024) . 'KB';
            $row['file_filesize'] = " ($fs)";
        }
        else
        {
            $row['file_filesize'] = '';
        }
    }


}

?>
