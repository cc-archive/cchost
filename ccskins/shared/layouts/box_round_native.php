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
    border: 1px solid #99A;
    margin: 7px;
    padding: 0px 8px 8px 8px;
    background: url('<?= $T->URL('images/native-box-bg.png') ?>') repeat-x top left;
}
.box h2 {
    margin: 0px 8px 8px 5%;
    padding: 4px;
}
.cc_form div#cc_form_help_container {
    /* width: 82%; */
}
.cc_form div#cc_form_help_container div.box {
    padding: 0.8em;
}

#user_description_html span.ufc_label {
    display: none;
}
#user_description_html  {
    padding-top: 1.5em;
}
</style>

