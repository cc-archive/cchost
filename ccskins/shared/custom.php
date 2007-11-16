<?
if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');
 



function _t_custom_Recent_Playlists($T,&$A) {
  $pl_lists = cc_recent_playlists();

    ?><p><?= $T->String('str_recent_playlists') ?></p>
    <ul>
<?  foreach( $pl_lists as $pl_list ) 
    {
        ?><li><a href="<?= $A['home-url']?>playlist/browse/<?= $pl_list['cart_id']?>">
            <?= cc_strchop($pl_list['cart_name'],12); ?></a></li><?
    }?>
    </ul>
    <a href="<?= $A['home-url']?>playlist/browse" class="cc_more_menu_link"><?= $T->String('str_more_playlists') ?>...</a>
<?
}

function _t_custom_Support_CC($T,&$A) {
  ?><p>Support CC</p>
    <ul ><li>
        <a href="http://creativecommons.org/support/">
            <img  src="http://creativecommons.org/images/support/2006/spread-3.gif" border="0" />
        </a>
    </li></ul>
    <?
}

function _t_custom_ChangeProfile($T,&$A) {
    require_once('cclib/cc-template.inc');
    ?><p>Change Profiles</p>
    <ul>
     <li>See this page in a </br> different way</li>
     <li><select></select></li>
   </ul>
   <?
}

?>