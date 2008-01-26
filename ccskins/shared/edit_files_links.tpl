<?/*
    [file_id] => 12744
    [file_upload] => 12735
    [file_name] => admin_-_2fingersGlow.png
    [file_nicname] => png
    [file_format_info] => Array
        (
            [media-type] => image
            [format-name] => image-png-png
            [default-ext] => png
            [mime_type] => image/png
            [dim] => Array
                (
                    [0] => 87
                    [1] => 91
                )
      )
    [file_filesize] =>  (6KB)
    [file_order] => 0
*/?>
<style type="text/css">
ul.ddex    { list-style: none; padding: 0px; width: 70%; margin:0px auto;}
ul.ddex li { padding: 4px; margin: 5px; border: 1px solid #999}
div.drag_handle { cursor: move;  }
div.fn {
    font-size: 1.2em;
    margin-bottom: 6px;
}
li.file_desc div.c {
    float: left;
    padding: 2px;
    margin: 0px 0px 0px 20px;
    border: 1px solid;
}
div.cmd_link{
    clear: both;
    margin: 1.0em;
}
</style>

<ul id="file_order" class="ddex">
%loop(field/files,F)%
    <li class="file_desc dark_border light_bg dark_color" id="file_order_%(#i_F)%">
        <div class="fn">%(#F/file_name)%</div>
        <div style="margin: 6px;">
            <? if( $c_F > 1 ) { ?>
                <div class="c light_border drag_handle">%text(str_file_drag_this)%</a></div>
            <? } ?>
            <div class="c light_border">
                <a href="%(field/urls/upload_nicname_url)%/%(#F/file_id)%">%text(str_file_nicname_this)%</a> %(#F/file_nicname)%
            </div>
            <div class="c light_border"><a href="%(field/urls/upload_replace_url)%/%(#F/file_id)%">%text(str_file_replace_this)% </a></div>
            <div class="c light_border" id="del_cmd_%(#i_F)%" <? if( $i_F == 1 ) { ?>style="display:none"<?}?>><a href="%(field/urls/upload_delete_url)%/%(#F/file_id)%">%text(str_file_delete_this)%</a></div>
            <div style="clear:both;">&nbsp;</div>
        </div>
    </li>
%end_loop%
</ul>
<div class="cmd_link">
    <a href="%(field/urls/upload_new_url)%">%text(str_file_add_new)%</a>
</div>
<? if( count($A['field']['files']) > 1 ) { ?>
    <div class="cmd_link" id="submit_order_link" style="display:none">
        <a id="submit_file_order" href="javascript://submit order">%text(str_file_submit_order)%</a>
    </div>
    <script type="text/javascript">
    var _first_file = 1;
    var upload_id = %(field/upload_id)%;

    function on_reorder_click()
    {
        var _file_order = Sortable.serialize('file_order');
        var url = '%(field/urls/upload_jockey_url)%' + q + _file_order;
        new Ajax.Request( url, { method:'get' } );
    }

    Event.observe('submit_file_order','click',on_reorder_click);

    function on_file_drop(a)
    {
        $('submit_order_link').style.display = 'block';

        try {
            if( _first_file != a.childNodes[0].id )
            {
                var id_old = _first_file;
                var id_new = a.childNodes[0].id.match(/([0-9]+)$/)[1];
                $('del_cmd_' + id_old).style.display = 'block';
                $('del_cmd_' + id_new).style.display = 'none';
                _first_file = id_new;
            }
        } catch(e) {
            alert(e);
        }
    }

    Sortable.create("file_order",{handle:'drag_handle',constraint:false,onUpdate: on_file_drop});

    </script>
<? } ?>

<!--[if lt IE 7.]> 
<script>
    $$('.file_desc').each( function(e) {
        e.style.backgroundColor = 'transparent';
    });
</script>
<![endif]-->
