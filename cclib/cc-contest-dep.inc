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

require_once('cclib/cc-contest-table.inc');
require_once('cclib/cc-remix-forms.php');


//-------------------------------------------------------------------

/**
* Deprecated Contest API and event callbacks
*
*/
class CCContestDeprecated
{

    /**
    * Displays contest listing into the current page
    *
    * @param string $contest_short_name Optional: if this parameter is not null only one contest will be displayed, otherwise all contests.
    */
    function ViewContests($contest_short_name='')
    {
        $contests =& CCContests::GetTable();

        if( empty($contest_short_name) )
        {
            $contests->SetOrder('contest_deadline','DESC');
            $cs =& $contests->GetRecords();
        }
        else
        {
            $contests =& CCContests::GetTable();
            $c = $contests->GetRecordFromShortName($contest_short_name);
            CCPage::SetTitle($c['contest_friendly_name']);
            $c['contest-homepage'] = true;
            $cs = array( &$c );
        }

        CCPage::PageArg( 'contest_record', $cs, 'contest_listing' );
    }

    /**
    * List out contests
    *
    *
    * @param string $contest_short_name Short (internal) name of contest
    */
    function Contests($contest_short_name='')
    {
        CCPage::SetTitle(_('Browse Contests'));
        $this->ViewContests($contest_short_name);
    }

    /**
    * Delete a contest
    * 
    * (not implemented)
    * 
    * @param string $contest_short_name Internal contest name
    */
    function ContestDelete($contest_short_name)
    {
        if( !CCUser::IsAdmin() )
            return;

        CCPage::Prompt('no implemento');
    }

    /**
    * Handles contest/vote (this method exits the session)
    *
    * @param string $contest_short_name Short (internal) name of contest
    */
    function Vote($contest_short_name)
    {
		global $CC_GLOBALS;

        // this is meant to be shown in an IFRAME (or ajax div) so we just
        // return raw html

        // just make sure we're even supposed to be here
        $contests =& CCContests::GetTable();
        $C = $contests->GetRecordFromShortName($contest_short_name);

        if( !$C['contest_vote_online'] )
        {
            print('<h3>' . 
	          _('This contest does not support online voting') . '</h3>');
            cc_exit();
        }

        if( !empty($_POST['polls']) )
        {
            // user voted, count it and return

            CCPoll::Vote($contest_short_name);
            CCUtil::SendBrowserTo( ccl('contest',$contest_short_name) );
        }

        $polls =& CCPolls::GetTable();

        if( CCContests::OKToVote($C) )
        {
            if( !$polls->PollExists($contest_short_name) )
            {
                $entries =& $this->_contest_uploads($contest_short_name,CCUD_CONTEST_ENTRY);
                $pollinsert = array();
                foreach( $entries as $entry )
                {
                    $pollinsert[] = array( $contest_short_name,
                                           $entry['user_real_name'] . '/' . $entry['upload_name'] );
                }
                $columns = array( 'poll_id', 'poll_value' );
                $polls->InsertBatch($columns, $pollinsert);
            }

            $where['contest_short_name'] = $contest_short_name;
            $vote_expires = $contests->QueryItem('contest_vote_deadline',$where);
            $form = new CCPollsForm($contest_short_name,strtotime($vote_expires));
            //$form->SetHandler( ccl('contest',$contest_short_name) );
            print( $form->GenerateHTML() );
        }
        else
        {
            if( $C['contest_taking_submissions'] )
            {
                print('<h3>' . 
		_('Voting will open after submission period has ended') . 
		'</h3>');
            }
            elseif( !$C['contest_voting_open'] )
            {
                $data = $polls->GetPollingData($contest_short_name,'poll_numvotes');
                $data = array_merge($data,$C);
                print('<h3>' . _('Poll Results') . '</h3>');
                $args['poll_data'] = $data;
                $args['auto_execute'][] = 'polling_data';
                $template = new CCTemplate($CC_GLOBALS['skin-map'] );
                $template->SetAllAndParse($args,true);
            }
            else
            {
                if( !CCUser::IsLoggedIn() )
                    print('<h3>' . 
		          _('Voting is only open to registered users') . 
			  '</h3>');
                else
                    print('<h3>' . _('Results will be shown here after the voting period is closed') . '</h3>');
            }
        }

        cc_exit();
    }


    /* ------------------------------
        Class helpers
       ------------------------------ */
    /**
    * Internal helper method
    *
    * Returns the all the uploads for a given contest, filtered by type
    *
    * @param mixed $contest_name_or_id Short (internal) name of contest or the ID
    * @param string $systags System tag to filter on
    * @returns array $records Records based on parameter requests
    */
    function & _contest_uploads($contest_name_or_id,$systags)
    {
        $uploads =& CCUploads::GetTable();
        $uploads->SetSort( 'upload_date', 'DESC' );
        if( intval($contest_name_or_id) == 0 )
        {
            $uploads->SetTagFilter($systags . ',' . $contest_short_name, 'all');
            $where = '';
        }
        else
        {
            $uploads->SetTagFilter($systags,'any');
            $where['upload_contest'] = $contest_name_or_id;
        }
        $records =& $uploads->GetRecords($where);
        $uploads->SetTagFilter('');
        $uploads->SetSort('');
        return $records;
    }

}


?>
