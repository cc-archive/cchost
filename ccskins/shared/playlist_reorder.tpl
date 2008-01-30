<?
if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

/*%%
[meta]
    type = template_component
    desc = _('Playlist Reorder')
    dataview = playlist_reorder
    embedded = 1
[/meta]
[dataview]
function playlist_reorder_dataview()
{
    $sql =<<<EOF
        SELECT upload_id, upload_name 
        FROM cc_tbl_uploads 
        JOIN cc_tbl_cart_items ON cart_item_upload=upload_id
        %where%
        ORDER BY cart_item_order
EOF;

    $sql_count =<<<EOF
        SELECT COUNT(*)
        FROM cc_tbl_uploads 
        JOIN cc_tbl_cart_items ON cart_item_upload=upload_id
        %where%
EOF;

     return array( 'sql' => $sql,
                   'sql_count' => $sql_count,
                   'e'   => array() );
}                  
[/dataview]
%%*/?>
<!-- template playlist_reorder -->
<style type="text/css">
ul.ddex    { list-style: none; padding: 0px; width: 50%; margin:0px auto;}
ul.ddex li { padding: 1px; margin: 2px; border: 1px solid #999}
div.fn {
    font-size: 0.8em;
    margin-bottom: 3px;
    cursor: move;  
    overflow: hidden;
}
</style>
<div class="cc_form_about box"><?= $T->String('str_pl_drag_to_reorder') ?></div>
<ul id="file_order" class="ddex">
    %loop(records,R)%
    <li class="file_desc dark_border light_bg dark_color" id="file_order_%(#i_R)%">
    <div class="fn">%(#R/upload_name)%</div>
    </li>
    %end_loop%
</ul>

<div class="cmd_link">
    <a id="submit_file_order" href="javascript://playlist record">%text(str_pl_save_order)%</a>
</div>

<script type="text/javascript">
function on_reorder_click()
{
    var _file_order = Sortable.serialize('file_order');
    var url = home_url + 'playlist/editorder/%(playlist_id)%/cmd' + q + _file_order;
    window.location.href = url;
    return false;
}
Event.observe('submit_file_order','click',on_reorder_click);
Sortable.create("file_order",{constraint:false});
</script>
