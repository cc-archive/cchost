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
    ?><script>cc_round_boxes();</script><?
}

$A['end_script_blocks'][] = 'ccskins/shared/layouts/box_round.php/invoke_script';

?>

<script type="text/javascript" src="<?= $T->URL('js/round-box.js'); ?>"></script>
<link rel="stylesheet" type="text/css" href="<?= $T->URL('layouts/box_round.css'); ?>" title="Default Style" />
<style>
.cssbox, .cssbox_body, .cssbox_head, .cssbox_head h2,
.cssbox, .cssbox_body_mono, .cssbox_head_mono, .cssbox_head_mono h2 
{ background-image: url('<?= $T->URL('images/backbox-mono.png') ?>') } 
</style>
