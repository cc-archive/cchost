<?
/*
* Artistech Media has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file LICENSE.TXT.
* 
* You may use dig.ccMixter software in accordance with the
* terms of that license. You agree that you are solely 
* responsible for your use of dig.ccMixter software and you
* represent and warrant to Artistech Media that your use
* of dig.ccMixter software will comply with the CC-GNU-GPL.
*
* $Id$
*
*/

$query_args = array(
    'dataview' => 'diginfo',
    'tags'     => 'remix',
    'sinced'   => '2 weeks ago',
    'sort'     => 'rank',
    'limit'    => 10,
);

$doc_page       = 'popular';
$page_title     = 'dig.ccmixter Popular';
$featured_class = 'class="current"';
$page_div_id    = 'popularpage';
$h2_title       = 'Popular';
$results_func   = 'query_results';

require_once('pages/_canned_query.inc');

?>