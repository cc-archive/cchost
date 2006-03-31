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

// go back and get ccextras as checked into
// ccHost CVS

if (is_dir('ccextras')) 
{
    if ($cc_dh2 = opendir('ccextras')) 
    {
       while (($cc_file2 = readdir($cc_dh2)) !== false) 
       {
           if( preg_match('/.*\.php$/',$cc_file2) )
               require_once( 'ccextras/' . $cc_file2);
       }
       closedir($cc_dh2);
    }
}

?>