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
*
* How to close a contest:
*
* 1. Fill out the form at to http://ccmixter.org/media/contest/snap
*
*    This step might take a while depending on how many entries in 
*    the contest. You should end up at the home page for the final 
*    entries. Note the URL, this what you give to whoever is picking 
*    up the entries.
*
* 2. Open a shell and go to mixter's web root directory. Execute
*    the shell script called (contest_name)_snap.sh This step will 
*    copy all the entries from the upload directory to the final
*    entries so it might take a while.
*
* 3. To remove the artist's name and track name from the mp3s go to:
*    http://ccmixter.org/media/contest/tagentries/(contest_name)
*
* 
*/
if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCevents::AddHandler( CC_EVENT_MAP_URLS, array( 'MixterContest', 'OnMapUrls' ) );


class MixterContest
{
    function OnMapUrls()
    {
        CCEvents::MapUrl( ccp('contest','snap'), array( 'MixterContest', 'Snap' ), CC_ADMIN_ONLY );
        CCEvents::MapUrl( ccp('contest','tagentries'), array( 'MixterContest', 'TagEntries' ), CC_ADMIN_ONLY );
    }

    function TagEntries($contest)
    {
        require_once('mixter-lib/mixter-contest.inc');
        MixterContestAPI::TagEntries($contest);
    }
    function Snap($contest='')
    {
        require_once('mixter-lib/mixter-contest.inc');
        MixterContestAPI::Snap($contest);
    }
}

?>