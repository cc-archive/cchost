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
* Core defines for the system
*
* @package cchost
* @subpackage core
*/

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**#@+
* Access flags
*/
define('CC_MUST_BE_LOGGED_IN',   1 );
define('CC_ONLY_NOT_LOGGED_IN',  2 );
define('CC_DONT_CARE_LOGGED_IN', 4 );
define('CC_ADMIN_ONLY',          8 );
define('CC_OWNER_ONLY',          0x10 );
define('CC_SUPER_ONLY',          0x1000 );

define('CC_DISABLED_MENU_ITEM', 0x20 );
define('CC_DYNAMIC_MENU_ITEM',  0x40 );
/**#@-*/


/**#@+
* @access private
*
* These for intended for documenation only
*/
define('CC_AG_ED_PICKS', 1 );
define('CC_AG_EDPICK',CC_AG_ED_PICKS ); 
define('CC_AG_FEEDS', 2 );
define('CC_AG_FEED', CC_AG_FEEDS );
define('CC_AG_FILE_API', 3 );
define('CC_AG_UPLOADS', 4 );
define('CC_AG_UPLOAD', CC_AG_UPLOADS );
define('CC_AG_SUBMIT_FORMS', 5 );
define('CC_AG_SUBMIT_FORM', CC_AG_SUBMIT_FORMS );
define('CC_AG_RENDER', 6 );
define('CC_AG_FORUMS', 7 );
define('CC_AG_FORUM', CC_AG_FORUMS ); 
define('CC_AG_HIDI', 8 );
define('CC_AG_USER', 9 );
define('CC_AG_SAMPLE_POOLS', 10 );
define('CC_AG_SAMPLE_POOL', CC_AG_SAMPLE_POOLS );
define('CC_AG_REVIEWS', 11 );
define('CC_AG_RATINGS', CC_AG_REVIEWS );
define('CC_AG_SEARCH', 12 );
define('CC_AG_TAGS', 13 );
define('CC_AG_NAVTABS', 14 );
define('CC_AG_VIEWFILE', 15 );
define('CC_AG_MISC_ADMIN', 16 );
define('CC_AG_ADMIN_MISC', CC_AG_MISC_ADMIN );
define('CC_AG_CONTESTS', 17 );
define('CC_AG_QUERY', 18 );
 
function cc_get_access_groups()
{
    return array( 
        CC_AG_ED_PICKS     => _('Ed Picks')   ,
        CC_AG_FEEDS        => _('Feeds')   ,
        CC_AG_FILE_API     => _('File API')   ,
        CC_AG_UPLOADS      => _('Uploads')   ,
        CC_AG_SUBMIT_FORMS => _('Submit Forms')   ,
        CC_AG_RENDER       => _('Render')   ,
        CC_AG_FORUMS       => _('Forums')   ,
        CC_AG_HIDI         => _('How I Did It')   ,
        CC_AG_USER         => _('User')   ,
        CC_AG_SAMPLE_POOLS => _('Sample Pools')   ,
        CC_AG_REVIEWS      => _('Ratings/Reviews')   ,
        CC_AG_SEARCH       => _('Search')   ,
        CC_AG_TAGS         => _('Folksonomy Tags')   ,
        CC_AG_NAVTABS      => _('Navigator Tabs')   ,
        CC_AG_VIEWFILE     => _('Viewfile')   ,
        CC_AG_MISC_ADMIN    => _('Misc. Admin Commands')   ,
        CC_AG_CONTESTS     => _('Contests')   ,
        CC_AG_QUERY        => _('Query')   ,
        );
}

function cc_get_roles()
{
    return array(
            CC_MUST_BE_LOGGED_IN => _('Registered users'),
            CC_ONLY_NOT_LOGGED_IN => _('Anonymous users only'),
            CC_DONT_CARE_LOGGED_IN => _('Everybody'),
            CC_ADMIN_ONLY          => _('Admin/Moderators'),
            CC_SUPER_ONLY          => _('Super admins')
            );
}

/**#@-*/

?>
