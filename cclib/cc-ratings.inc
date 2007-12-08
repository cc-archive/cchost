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
* Module for handling ratings
*
* @package cchost
* @subpackage ui
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

class CCRatingsHV
{
    /**
    * @access private
    */
    function _fill_scores(&$record,$prefix)
    {
        $configs =& CCConfigs::GetTable();
        $ratings_config = $configs->GetConfig('chart');

        $average = $record[$prefix . '_score'] / 100;
        $count = $record[$prefix . '_num_scores'];
        $stars = floor($average);

        if( empty($ratings_config['thumbs_up']) )
        {
            $record['thumbs_up'] = 0;

            $half  = ($record[$prefix . '_score'] % 100) > 25;

            for( $i = 0; $i < $stars; $i++ )
                $record['ratings'][] = 'full';

            if( $half )
            {
                $record['ratings'][] = 'half';
                $i++;
            }
            
            for( ; $i < 5; $i++ )
                $record['ratings'][] = 'empty';

            $record['ratings_score'] = number_format($average,2) . '/' . $count;
        }
        else
        {
            $record['thumbs_up'] = 1;
            $record['ratings_score'] = $count;
        }
    }

    /**
    * Event handler for {@link CC_EVENT_USER_ROW}
    *
    * Add extra data to a user row before display
    *
    * @param array &$record User record to massage
    */
    function OnUserRow(&$record)
    {
        if( !empty($record['user_num_scores']) ) // && $row['user_num_scores'] > 10 )
        {
            $this->_fill_scores($record,'user');
        }
    }
}
?>