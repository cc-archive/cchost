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
* $Header$
*
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

define( 'CC_POLL_COOKIE', 'loginsat');

class CCPollsForm extends CCForm
{
    function CCPollsForm($poll_id,$poll_end_time)
    {
        $this->CCForm();
        $polls =& CCPolls::GetTable();
        $data = $polls->GetPollingData($poll_id);
        $choices = array();
        foreach( $data['poll_entries'] as $pollinfo )
            $choices[$pollinfo['poll_valueid']] = $pollinfo['poll_value'];
        $fields = array(
            'poll_valueid' => array(
                        'label'      => '',
                        'form_tip'   => '',
                        'formatter'  => 'radio',
                        'value'      => '',
                        'options'    => $choices,
                        'flags'      => CCFF_NONE) );

        $this->AddFormFields( $fields );
        $this->SetSubmitText(cct('Vote'));
        $this->SetHiddenField('poll_over',$poll_end_time);
    }
}
        


class CCPolls extends CCTable
{
    function CCPolls()
    {
        $this->CCTable('cc_tbl_polls','poll_valueid');
    }

    /**
    * Returns static singleton of configs table wrapper.
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
            $_table = new CCPolls();
        return( $_table );
    }

    function GetPollingData($poll_id,$sort = 'poll_value')
    {
        $this->SetOrder($sort,'DESC');
        $where    = "poll_id = '$poll_id'";
        $numvotes = $this->QueryItem('SUM(poll_numvotes)',$where);
        $rows     =& $this->QueryRows($where);
        $count = count($rows);
        for( $i = 0; $i < $count; $i++ )
        {
            if( $numvotes )
                $rows[$i]['poll_percent'] = number_format((100 / $numvotes) * $rows[$i]['poll_numvotes'],2);
            else
                $rows[$i]['poll_percent'] = 0;
        }
        $arr = array( 'poll_entries' => &$rows,
                      'poll_total_votes' => $numvotes );
        return( $arr );
    }

    function PollExists($poll_id)
    {
        $count = $this->QueryItem('COUNT(*)',"poll_id = '$poll_id'");
        return( $count > 0 );
    }

    function AddVote($poll_valueid)
    {
        $updates['poll_valueid']  = $poll_valueid;
        $updates['poll_numvotes'] = 'poll_numvotes + 1';
        $this->Update($updates,false);
    }

}


class CCPoll
{
    function Vote($poll_id)
    {
        global $CC_GLOBALS;

        if( array_key_exists( 'poll_valueid', $_POST) && !CCPoll::AlreadyVoted($poll_id))
        {
            $poll_valueid = CCUtil::StripText($_POST['poll_valueid']);
            $polls =& CCPolls::GetTable();
            $polls->AddVote($poll_valueid);
            $poll_over    = CCUtil::StripText($_POST['poll_over']);
            cc_setcookie(md5($poll_id), time(), $poll_over);
        }
    }

    function AlreadyVoted($poll_id)
    {
        return( array_key_exists( md5($poll_id), $_COOKIE ) );
    }

}

?>