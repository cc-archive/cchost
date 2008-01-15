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

$A['end_script_blocks'][] = 'ccskins/shared/layouts/box_round.php/invoke_script';

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

<!--[if lt IE 7.]>
<style type="text/css">
.cssbox, .cssbox_body, .cssbox_head, .cssbox_head h2
{ background-image: url('<?= $T->URL('images/backbox-ie.gif') ?>') } 
</style>
<link rel="stylesheet" type="text/css" href="<?= $T->URL('layouts/box_round_ie6.css'); ?>" title="Default Style" >
<![endif]-->

<script type="text/javascript">
var round_box_enabled = 1;
</script>
