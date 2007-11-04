<?
if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

//------------------------------------- 
function _t_share_share_popup($T,&$A) 
{
  
?>
<div  id="share_div"></div>
<div  id="share_email">
<a  href="<?= $A['PUB']['email_url']?>" class="cc_gen_button"><span ><div  id="inner_share"><?= _('Email a friend')?></div></span></a>
</div>
<script  type="text/javascript">
  new ccShareLinks( { url: '<?= $A['PUB']['bookmark_url']?>', title:'<?= addslashes($A['PUB']['bookmark_title']) ?>', inPopUp: false} );
</script>
<?
} 
  
?>