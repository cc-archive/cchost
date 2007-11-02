<div id="skin_div"><i><?= $GLOBALS['str_thinking'] ?>...</i></div>
<?
$skins = $A['field']['skins'];
$skin_infos = array();
foreach( $skins as $skin => $name )
{
    $about = preg_replace('/skin\.[a-z]+$/','about.php',$skin);
    if( file_exists($about) )
    {
        $func_name = '_t_about_' . basename(dirname($about));
        require_once($about);
        $skin_infos[$skin] = $func_name();
    }
    else
    {
        $skin_infos[$skin] = '<i>no skin properties available</i>';
    }
}

require_once('cclib/zend/json-encoder.php');
$skin_infos = CCZend_Json_Encoder::encode($skin_infos); 

?>
<script>
skin_form_field = Class.create();

skin_form_field.prototype = {

    file_field: null,
    skin_div:  null,
    skin_infos: <?= $skin_infos ?>,

    initialize: function() {
        this.skin_div = $('skin_div');
        this.file_field = $('skin-file');
        Event.observe(this.file_field,'change',this.onSkinChange.bindAsEventListener(this));
        var sel = this.file_field.selectedIndex || 0;
        var skin = this.file_field.options[ sel ].value;
        this.skin_div.innerHTML = this.skin_infos[skin];
    },

    onSkinChange: function() {
        var skin = this.file_field.options[ this.file_field.selectedIndex ].value;
        this.skin_div.innerHTML = this.skin_infos[skin];
    }

}

new skin_form_field();
</script>
   