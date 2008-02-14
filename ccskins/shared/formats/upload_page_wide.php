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

/*
[meta]
    type     = page
    desc     = _('Single upload page (wide)')
    dataview = upload_page
[/meta]
*/
?>

<style type="text/css">
div#upload_wrapper{float:left;width:100%}
div#upload_middle{margin: 0 30% 0 20%;padding-left:2.0em;}
div#upload_sidebar_box{float:left;width:30%;margin-left:-30%}
div#upload_menu_box{float:left;width:20%;margin-left:-100%;padding-left:1.5em;}
</style>

<!--[if IE]> 
<style type="text/css">
div#upload_wrapper{float:left;width:100%;}
div#upload_middle{margin: 0 35% 0 23%;}
div#upload_sidebar_box{float:left;width:30%;margin-left:-30%;}
div#upload_menu_box{float:left;width:23%;margin-left:-85%;}
</style>
<![endif]-->

<? $T->Call('formats/upload_page_shared.tpl'); ?>