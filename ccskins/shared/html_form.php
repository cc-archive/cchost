<? if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');
?>
<!-- tempalte html_form -->
<link rel="stylesheet" type="text/css" href="<?= $T->URL('css/form.css') ?>" title="Default Style" />
<script type="text/javascript" src="<?= $T->URL('js/form.js') ?>"></script>

<?
function _t_html_form_html_form(&$T,&$A) 
{
    $F =& $A['curr_form'];

    if ( !empty($F['form_id']))
        print "<script type=\"text/javascript\">form_id = '{$F['form_id']}';</script>\n";

    $onsubmit = ''; // empty($F['hide_on_submit']) ? '' : 'onsubmit="return the_formMask.dull_screen();" ';
    $enctype  = empty($F['form-data'])      ? '' : 'enctype="' . $F['form-data'] . '"';
    $html =<<<EOF
    <form  action="{$F['form_action']}" 
             method="{$F['form_method']}" 
             class="cc_form" 
             name="{$F['form_id']}" id="{$F['form_id']}" 
              {$onsubmit} {$enctype} >
EOF;
    
    print $html;

    if ( !empty($F['form_macros']))
        foreach( $F['form_macros'] as $macro )
           $T->Call($macro);

    if ( !empty($F['html_form_grid_columns'])) 
    {
        if( empty($F['form_fields_macro']) )
            $T->Call('grid_form_fields');
        else
            $T->Call($F['form_fields_macro']);
    }

    if ( !empty($F['html_form_fields']))
    {
        if( empty($F['form_fields_macro']) )
            $T->Call('form_fields');
        else
            $T->Call($F['form_fields_macro']);
    }

    if ( !empty($F['submit_text'])) 
    {
        $submit_text = $T->String($F['submit_text']);
        ?><input  type="submit" name="form_submit" id="form_submit" class="cc_form_submit" value="<?= $submit_text ?>"></input><?
    }

    if ( !empty($F['html_hidden_fields'])) 
    {
        foreach( $F['html_hidden_fields'] as $H )
            print "\n<input  type=\"hidden\" name=\"{$H['hidden_name']}\" id=\"{$H['hidden_name']}\" value=\"{$H['hidden_value']}\" />";
    }

    print "</form>\n";

    if( !empty($A['post_form_goo']) )
    {
        $T->Call('post_form_goo');
        unset($A['post_form_goo']);
    }

    if( !empty($F['form_submit_trap']) )
    {
        ?>
<script type="text/javascript">
    // ajax trapper
    new <?= $F['form_submit_trap']?>(form_id);
</script>
        <?
    }

} // END: function html_form



//------------------------------------- 
function _t_html_form_submit_forms(&$T,&$A) 
{
   ?>
    <div class="cc_submit_forms_outer">
       <div  id="manage_box" style="display:none;">
        <div class="cc_submit_forms box">
            <h2><?= $T->String('str_file_manage') ?></h2>
            <table><tr>
            <td><a href="javascript://sort by date" class="small_button" style="display:none" id="files_by_date"><span><?= $T->String('str_file_sort_by_date') ?></span></a></td>
            <td><a href="javascript://sort by name" class="small_button" style="display:default" id="files_by_name"><span><?= $T->String('str_file_sort_by_name') ?></span></a></td>
            </tr></table>
            <br />
            <div id="files_manage_target" style="height:200px;overflow:scroll;border:2px solid #555;">
            </div>
        </div>
        </div>
<script type="text/javascript">

ccFileAdderPicker = Class.create();

ccFileAdderPicker.prototype = {

    initialize: function() {
        var url = query_url + 'f=html&t=manage_files&limit=300&dataview=default&user=' + user_name;
        new Ajax.Request( url,  { method: 'get', onComplete: this.gotFiles.bind(this) });
        Event.observe( 'files_by_date', 'click', this.onFilesBy.bindAsEventListener(this,'date') );
        Event.observe( 'files_by_name', 'click', this.onFilesBy.bindAsEventListener(this,'name') );
    },

    onFilesBy: function(event, type) {
        var url = query_url + 'f=html&t=manage_files&limit=300&dataview=default&user=' + user_name;
        var on, off;
        if( type == 'name' )
        {
            url += '&sort=name&ord=asc';
            on = 'date';
            off = 'name';
        }
        else
        {
            on = 'name';
            off = 'date';
        }
        $('files_by_' + on).style.display = '';
        $('files_by_' + off).style.display = 'none';
        new Ajax.Request( url,  { method: 'get', onComplete: this.gotFiles.bind(this) });
    },

    gotFiles: function(resp) {
        try {
            if( !resp.responseText ) 
                return;
            $('manage_box').style.display = 'block';
            $('files_manage_target').innerHTML = resp.responseText;
            this.updatePickers();
        } 
        catch(err)
        {
            alert(err);
        }
    },

    updatePickers: function() {
        var me = this;
        $$('.add_file_picker').each( function(e) {
            Event.observe( e, 'change', me.go_to_file_adder.bindAsEventListener(me,e) );
        });
    },

    go_to_file_adder: function( event, select )
    {
        var type = select.options[ select.selectedIndex ].value;
        if( !type )
            return;
        var id = select.id.match(/[0-9]+$/);
        var url = home_url + 'file/add/' + id + q + 'atype=' + type;
        document.location = url;
    }
}

new ccFileAdderPicker();

</script>
   <?

    foreach($A['submit_form_infos'] as $SI )
    {
        ?><div  class="cc_submit_forms box"><?

        if ( !empty($SI['logo'])) 
        {
            ?><img  src="<?= $T->URL($SI['logo']) ?>" /><?
        }

        ?><h2 ><?= $T->String($SI['text']) ?></h2>
        <div  class="cc_submit_form_help"><?= $T->String($SI['help']) ?></div>
        <div  class="cc_submit_form_url"><?
            if ( !($SI['quota_reached']) )
                { ?><a  href="<?= $SI['action']?>"><?= $T->String($SI['text']) ?></a><? }
            else
                { ?><span  class="cc_quota_message"><?= $T->String($SI['quota_message']) ?></span><? }

        ?></div>
        </div><?
    } 

    ?></div><?
} // END: function submit_forms


//------------------------------------- 
function _t_html_form_add_type_stuffer(&$T,&$A) 
{
?> 
<script>
Event.observe( 'file_type', 'change', function() 
    { 
        var ft = $('file_type');
        var sel = ft.options[ ft.selectedIndex ];
        var text = sel.value ? sel.text : '';
        $('type_hint_target').innerHTML = '<b>' + text + '</b>';
    }
    );
</script>
<?

}

//------------------------------------- 
function _t_html_form_show_form_about(&$T,&$A) 
{
    ?><div id="cc_form_help_container"><div class="box"><?
    foreach( $A['curr_form']['form_about'] as $FA )   
    {
        ?><div  class="cc_form_about"><?= $T->String($FA) ?></div><?
    }
    
    ?></div></div><?

} // END: function show_form_about

?>