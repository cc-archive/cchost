<?
/*
[meta]
    type  = box_shape
    image = layouts/images/rbox_layout002.gif
    desc  = _('Rounded Boxes [native]')
[/meta]    
*/
?>

<script type="text/javascript" src="<?= $T->URL('js/DD_roundies_0.0.2a.js'); ?>"></script>
<script type="text/javascript" >
 DD_roundies.addRule('div.box', '8px',true);
</script>

<style type="text/css">
.box {
    border: 1px solid black;
    margin: 7px;
    padding: 0px 8px 8px 8px;
    background: url('<?= $T->URL('images/native-box-bg.png') ?>') no-repeat top left;
}
.box h2 {
    margin: 0px 8px 8px 5%;
    padding: 4px;
}
</style>

