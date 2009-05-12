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
* Module for managing Creative Commons licenses
*
* @package cchost
* @subpackage feature
*/
if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');


/**
* Wrapper class for license information table
*
* This is just syntantic sugar on to of CCTable
*/
class CCLicenses extends CCTable
{
    /**
    * Constructor
    *
    */
    function CCLicenses()
    {
        $this->CCTable('cc_tbl_licenses','license_id');
        $this->AddExtraColumn('0 as license_checked');
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
            $_table = new CCLicenses();
        return( $_table );
    }

    /**
    * Get rows with enabled flag turned on (this is bogus and will change)
    *
    * @returns array $rows Returns CCLicense table object rows
    */
    function GetEnabled($looser_than = -1)
    {
        $configs =& CCConfigs::GetTable();
        $licenses = $configs->GetConfig('licenses');
        if( empty($licenses) )
        {
            $rows = $this->QueryRows("license_id = 'attribution'");
            return($rows);
        }
        $where = array();
        foreach($licenses as $lic => $on )
        {
            if( $on )
                $where[] = "(license_id = '$lic')";
        }
        $where = implode(' OR ' ,$where);
        if( $looser_than != -1 )
            $where = "($where) AND (license_strict <= $looser_than)";
        $rows = $this->QueryRows($where);
        if( empty($rows) )
            $rows = $this->QueryRows("license_id = 'attribution'");
        if( !empty($rows) )
            $rows[0]['license_checked'] = true;
        return( $rows );
    }
}

?>
