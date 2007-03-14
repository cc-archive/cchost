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
* Implements the user interface and database management for contests
*
* @package cchost
* @subpackage contest
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');


/**
* Wrapper for database Contest table
*
*/
class CCContests extends CCTable
{
    var $_publish_filter;

    /**
    * Constructor -- don't use new, use GetTable() instead
    *
    * @see GetTable
    */
    function CCContests()
    {
        $this->CCTable('cc_tbl_contests','contest_id');
        $this->AddJoin(new CCUsers(),'contest_user');

        if( !CCUser::IsAdmin() )
            $_publish_filter = '(contest_publish > 0)';
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
            $_table = new CCContests();
        return($_table);
    }

    /**
    * Returns the display name for a contest given the short (internal) name
    *
    * @param string $name Short (internal) name of contest
    * @return string $long_name Display (friendly) name of contest
    */
    function GetFriendlyNameFromShortName($name)
    {
        $where['contest_short_name'] = $name;
        return( $this->QueryItem( 'contest_friendly_name', $where ) );
    }

    /**
    * Returns the database ID for a contest given the short (internal) name
    *
    * @param string $name Short (internal) name of contest
    * @return integer $id Database ID of contest
    */
    function GetIDFromShortName($name)
    {
        $where['contest_short_name'] = $name;
        return( $this->QueryItem( 'contest_id', $where ) );
    }

    /**
    * Returns the display ready record for a contest given the short (internal) name
    *
    * This will return a full record (as opposed to raw database row) for a contest
    *
    * @param string $name Short (internal) name of contest
    * @return array $record Full record (as opposed to raw db row) for contest
    */
    function & GetRecordFromShortName($name)
    {
        $where['contest_short_name'] = $name;
        $row = $this->QueryRow($where);
        $R =& $this->GetRecordFromRow($row);
        return $R;
    }

    /**
    * Returns a series of full display ready records of contests
    *
    * @see CCTable::GetRecordFromRow()
    * @see CCTable::Query()
    * @param mixed $where array or string specifying row filter
    * @param bool  $expand true if you need the local command menus for each row
    * @param integer $offset Offset into database
    * @param integer $limit Number of records to return
    * @returns array $records Array of display ready records from Contests table
    */
    function & GetRecords($where='',$expand=true, $offset=0, $limit=0)
    {
        $this->SetOffsetAndLimit($offset,$limit);
        $qr = $this->Query($where);
        $this->SetOffsetAndLimit(0,0);
        $records = array();
        while( $row = mysql_fetch_assoc($qr) )
        {
            $record = $this->GetRecordFromRow($row,$expand);
            $records[] = $record;
        }

        $ret =& $records;
        return $ret;
    }

    /**
    * Returns the display ready records for the currently open contests
    *

    * @see CCTable::GetRecordForRow()
    * @param bool  $expand true if you need the local command menus for each row
    * @param integer $limit Number of contests to return
    * @returns array $records Array of display ready records from Contests table
    */
    function & GetOpenContests($expand=false,$limit=0)
    {
        $where =<<<EOF
         contest_publish 
           AND
         NOW() > contest_open
           AND 
         (
           (NOW() < contest_deadline) OR 
           (NOW() < contest_vote_deadline)
         )
EOF;
        $r = $this->GetRecords($where,$expand,0,$limit);
        return $r;
    }

    /**
    * Returns the display ready records for contests no longer open
    *
    * @see CCTable::GetRecordForRow()
    * @param bool  $expand true if you need the local command menus for each row
    * @returns array $records Array of display ready records from Contests table
    */
    function & GetPastContests($expand=false)
    {
        $where = '(contest_deadline < NOW()) AND (contest_vote_deadline < NOW())';
        return( $this->GetRecords($where,$expand) );
    }

    /**
    * Populate a database row for a contest with specific state flags
    *
    * Upon return there will be several boolean flags regarding the
    * current state of this contest. 
    *
    * <code>
    * $a['contest_taking_submissions']  // true if NOW is before deadline
    * $a['contest_voting_open']         // true if voting is allowed and NOW is after deadline but before voting deadline
    * $a['contest_show_results']        // true if voting is allowed and NOW is after voting deadline
    * $a['contest_can_browse_entries']  // true if browing is always allowed or NOW is after contest deadline
    * $a['contest_over']                // true is NOW after all submissions and voting
    *  </code>
    *
    * @param array $row Reference to contest database row
    */
    function GetOpenStatus(&$row)
    {
        $row['contest_taking_submissions'] = false;
        $row['contest_voting_open']        = false;
        $row['contest_show_results']       = false;
        $row['contest_can_browse_entries'] = false;
        $row['contest_over']               = false;
        if( $row['contest_publish'] > 0 )
        {
            $open     = strtotime($row['contest_open']);
            $entries  = strtotime($row['contest_entries_accept']);
            $deadline = strtotime($row['contest_deadline']);
            $now      = time();
            if( ($now > $open) && ($now < $deadline) )
            {
                if( $now > $entries )
                {
                    $row['contest_taking_submissions'] = true;

                    if( $row['contest_auto_publish'] )
                        $row['contest_can_browse_entries'] = true;
                }
            }
            else
            {
                if( $now > $open )
                {
                    if( $now > $entries )
                        $row['contest_can_browse_entries'] = true;

                    if( $row['contest_vote_online'] )
                        $deadline = strtotime($row['contest_vote_deadline']);

                    if( $now < $deadline )
                    {
                        $row['contest_voting_open'] = true;
                    }
                    else
                    {
                        $row['contest_over'] = true;
                    }
                }
                else
                {
                    //$row['contest_over'] = true;
                }
            }
        }
    }

    /**
    *  Converts a raw database row to a semantically rich (display ready) record
    *
    * @param array $row Reference to database row
    * @param bool  $expand true if you want to include local menu commands for each record
    */
    function & GetRecordFromRow(&$row,$expand = true)
    {
        if( !$row['contest_id'] )
            return $row;

        $this->GetOpenStatus($row);

        $row['contest_url']               = ccc( $row['contest_short_name'] );
        $row['contest-homepage']          = false;

        $row['contest_description_html'] = CCUtil::TextToHTML($row['contest_description']);

        if( $row['contest_bitmap'] )
        {
            $relative = CCContestHV::_get_upload_dir($row);
            $row['contest_bitmap_url'] =  ccd( $relative, $row['contest_bitmap'] );
        }

        $row['contest_deadline_fmt']      = date(' l F jS, Y \a\t g:ia',
                                              strtotime($row['contest_deadline']));
        $row['contest_vote_deadline_fmt'] = date(' l F jS, Y \a\t g:ia',
                                              strtotime($row['contest_vote_deadline']));
        if( $row['contest_taking_submissions'] )
        {
            $row['contest_states'][] = 
                array( 'css_class' => 'cc_contest_open',
                       'text'      => $row['contest_friendly_name'] . 
                                      _(' is currently open and taking submissions.') );

            $row['contest_states'][] = 
                array( 'css_class' => 'cc_contest_open',
                       'text'      => _('Submissions allowed until: '));

			$row['contest_states'][] = 
                array( 'css_class' => '',
                       'text'      => $row['contest_deadline_fmt'] );

            if( !CCUser::IsLoggedIn() )
            {
				$row['contest_states'][] = 
					array( 'css_class' => 'cc_contest_open',
						   'text'      => _('(Only logged in users can submit entries.)') );
            }
        }
        else
        {
            if( $row['contest_over'] )
            {
                $row['contest_states'][] = 
                    array( 'css_class' => 'cc_contest_closed',
                           'text'      => $row['contest_friendly_name'] . 
                                          _(' is not taking submissions any more.') );

                $row['contest_states'][] = 
                    array( 'css_class' => 'cc_contest_closed',
                           'text'      => _('Submissions stopped after: ') );

                $row['contest_states'][] = 
                    array( 'css_class' => '',
                           'text'      => $row['contest_deadline_fmt'] );
            }
            else
            {
                $row['contest_states'][] = 
                    array( 'css_class' => 'cc_contest_closed',
                           'text'      => $row['contest_friendly_name'] . 
                                          _(' is not taking submissions yet.') );

                $row['contest_states'][] = 
                    array( 'css_class' => 'cc_contest_closed',
                           'text'      => _('Submissions starts after: ') );

                $row['contest_states'][] = 
                    array( 'css_class' => '',
                           'text'      => $row['contest_open'] );
            }
        }

        if( $row['contest_voting_open'] )
        {
            $row['contest_states'][] = 
                array( 'css_class' => 'cc_contest_voting_status',
                       'text'      => _('Voting is open until ') .
                                      $row['contest_vote_deadline_fmt'] ) ;

        }

        if( $row['contest_vote_online'] )
            $row['contest_vote_url'] = ccl('contest', 'vote', $row['contest_short_name'] );

        if( $expand )
        {
            CCEvents::Invoke(CC_EVENT_CONTEST_ROW,array(&$row));
        }

        return( $row );
    }

    /**
    * Verifies that the current user is allowed to vote in the current contest
    *
    * @param array $record Contest record to check
    */ 
    function OKToVote(&$R)
    {
        return( empty($_REQUEST['polls']) && CCUser::IsLoggedIn() && 
                   $R['contest_voting_open'] && 
                  !CCPoll::AlreadyVoted($R['contest_short_name']) );

    }

    /**
    * Overwrites base class to add specific publishing and other filters
    * 
    * @param mixed $where string or array representing WHERE clause
    * @param string $columns SELECT will be limited to these columns
    * @return string $select Fully formed SELECT statement
    */
    function _get_select($where,$columns='*')
    {
        $where = $this->_where_to_string($where);

        if( !empty($this->_publish_filter) )
        {
            if( empty($where) )
                $where = $this->_publish_filter;
            else
                $where = '($where) AND ({$this->_publish_filter})';
        }
        $sql = parent::_get_select($where,$columns);
        return($sql);

    }
}

?>