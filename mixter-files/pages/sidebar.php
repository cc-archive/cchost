<?
if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

function _t_sidebar_init($T,&$targs) {
    
}

//------------------------------------- 
function _t_sidebar_Latest_ccRemixes($T,&$A) {
  } // END: function Latest_ccRemixes


//------------------------------------- 
function _t_sidebar_ccM_w_playlists($T,&$A) {
  $T->Call('sidebar.xml/ccRecent_Playlists');
$T->Call('custom.xml/Editorial_Picks');
$T->Call('custom.xml/Recent_Reviews');
} // END: function ccM_w_playlists


//------------------------------------- 
function _t_sidebar_bookmarks($T,&$A) {
  
?><div  style="margin: 8px 0px 4px 0px">
<script  type="text/javascript">
  addthis_url    = location.href;   
  addthis_title  = document.title;  
  addthis_pub    = 'fourstones';     
</script>
<script  type="text/javascript" src="http://s7.addthis.com/js/addthis_widget.php?v=12"></script>
</div>
<?
} // END: function bookmarks


//------------------------------------- 
function _t_sidebar_ccM_Sidebar($T,&$A) {
  $T->Call('custom.xml/Ratings_Chart');
$T->Call('custom.xml/Editorial_Picks');
$T->Call('custom.xml/Recent_Reviews');
} // END: function ccM_Sidebar


//------------------------------------- 
function _t_sidebar_ccRecent_Playlists($T,&$A) {
  $A['pl_lists'] = CC_recent_playlists();

?><p ><?= _('Recent Playlists');?></p>
<ul  condition="pl_lists">
<?

$carr101 = $A['pl_lists'];
$cc101= count( $carr101);
$ck101= array_keys( $carr101);
for( $ci101= 0; $ci101< $cc101; ++$ci101)
{ 
   $A['pl_list'] = $carr101[ $ck101[ $ci101 ] ];
   
?><li ><a  href="<?= $A['home-url']?>playlist/browse/<?= $A['pl_list']['cart_id']?>"><?= CC_strchop($A['pl_list']['cart_name'],12);?></a></li><?
} // END: for loop

?></ul>
<a  href="<?= $A['root-url']?>media/view/media/playlists" class="cc_more_menu_link"><?= CC_Lang('More playlists...')?></a>
<?
} // END: function ccRecent_Playlists

?>