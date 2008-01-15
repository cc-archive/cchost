<?
/*
[meta]
    type  = box_shape
    image = layouts/images/rbox_layout002.gif
    desc  = _('Rounded Boxes')
[/meta]    
*/


function _t_box_round_invoke_script($A,$T)
{
    ?><script type="text/javascript">cc_round_boxes();</script><?
}

$A['end_script_blocks'][] = __FILE__ . '/invoke_script';

?>

<script type="text/javascript" src="<?= $T->URL('js/round-box.js'); ?>"></script>
<link rel="stylesheet" type="text/css" href="<?= $T->URL('layouts/box_round.css'); ?>" title="Default Style" />
<!--[if gt IE 6.]> 
<link rel="stylesheet" type="text/css" href="<?= $T->URL('layouts/box_round_ie.css'); ?>" title="Default Style" />
<![endif]-->

<style type="text/css">
.cssbox, .cssbox_body, .cssbox_head, .cssbox_head h2
{ background-image: url('<?= $T->URL('images/backbox-mono.png') ?>') } 
</style>


<script type="text/javascript">
var round_box_enabled = 1;
</script>

<!--[if lt IE 7.]>
<? require_once( dirname(__FILE__) . '/box_square.php' ); ?>
<style>
.box {
    overflow: hidden;
}
</style>
<script type="text/javascript">
var round_box_enabled = null;
var disable_round_box = true;
</script>
<![endif]-->
