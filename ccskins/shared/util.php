<?


function _t_util_print_html_content($T,&$A)
{
    if( empty($A['html_content']) )
        return;

    foreach( $A['html_content'] as $html )
        print $html;
}

function _t_util_print_forms($T,&$A)
{
    if( empty($A['forms']) )
        return;

    foreach( $A['forms'] as $form_info )
    {
        $form = $form_info[1];
        if( !empty($form['string_files']) )
        {
            foreach($form['string_files'] as $file )
            {
                $path = $T->Search($file);
                if( empty($path) )
                    die("Can't find string file: $file");
                require_once($path);
            }
        }
        $A['curr_form'] = $form;
        $T->Call($form_info[0]);
    }
}

function _t_util_hide_upload_form($T,&$A)
{
    $msg = str_replace("\n", ' ', addslashes($GLOBALS['str_uploading_msg']));
    
    ?>
<style>
#bodymask {
        position: absolute;
        top: 0px;
        left: 0px;
        width: 100%;
        height: 400%;
        background-color: #999;
        opacity: 0.8;
        z-index: 100;
}
#maskmsg {
    position: absolute;
    top: 0px;
    left: 0px;
    margin: 10%;
    opacity: 1.0; 
    border: solid 2px black;
    background-color: white;
    padding: 5%;
    text-align: center;
    vertical-align: middle;
    z-index: 103;
    font-size: 23px;
    font-weight: bold;
}
</style>
<? 
$spinner = $T->URL('images/spinner.gif');
if( !empty($spinner) )
    $spinner = "<br /><br /><img src=\"{$spinner}\" />";
?>
<script>
var formMask = Class.create();

formMask.prototype = {

    initialize: function()
    {
        //Event.observe(form_id,'submit', this.dull_screen.bindAsEventListener(this) );
    },

    dull_screen: function()
    { 
         new Insertion.Before( $('banner'), '<div id="bodymask"><span>uploading...</span><div id="maskmsg"><?= $msg ?><?= $spinner ?></div>' );
         Event.observe('bodymask','click',this.killClick.bindAsEventListener(this),true);
         Event.observe('bodymask','keypress',this.killClick.bindAsEventListener(this),true);
         Element.scrollTo('bodymask');
        //Modalbox.show( $('upload_msg'), {title: null, overlayClose: false, width: 400} );
        return true;
    },

    killClick: function(e)
    {
        Event.stop(e);
        return false;
    }


}

var the_formMask = new formMask();
</script>
    <?
}
?>