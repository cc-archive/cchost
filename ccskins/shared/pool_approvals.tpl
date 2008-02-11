<?
if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

/*
[meta]
    type = template_component
    desc = _('Pool approval (admin)')
    dataview = pool_approve
    embedded = 1
[/meta]
[dataview]
function pool_approve_dataview()
{
    $ccl = ccl('files') . '/';

    $sql =<<<EOF
        SELECT pool_item_id,pool_item_url,pool_item_name,pool_item_download_url,pool_item_extra,pool_name,
               pool_item_artist, upload_name,user_real_name,user_name,
               CONCAT( '$ccl', user_name, '/', upload_id ) as file_page_url
        FROM cc_tbl_pool_item
        JOIN cc_tbl_pools     ON pool_item_pool       = pool_id
        JOIN cc_tbl_pool_tree ON pool_tree_pool_child = pool_item_id
        JOIN cc_tbl_uploads   ON pool_tree_parent     = upload_id
        JOIN cc_tbl_user      ON upload_user          = user_id
        %where% AND (pool_item_approved = 0)
        GROUP BY pool_item_id
        %order%
        %limit%
EOF;

        $sql_count =<<<EOF
        SELECT COUNT(*)
        FROM cc_tbl_pool_item
        JOIN cc_tbl_pools     ON pool_item_pool       = pool_id
        JOIN cc_tbl_pool_tree ON pool_tree_pool_child = pool_item_id
        %joins%
        %where% AND (pool_item_approved = 0)
EOF;

    return array( 'sql' => $sql,
                  'sql_count' => $sql_count,
                  'e'   => array( ) );
}
[/dataview]
*/
$post_url = ccl( 'admin', 'pools', 'approve', 'submit' );
$heads = array( _('Approve'), _('Delete'), _('None'), _('Upload'),_('Author'), _('Site/Links') );
$tr = array( '<' => '&lt;', '>' => '&gt' );
?>
<!-- tempalte pool_approvals -->
<style>
.cc_pool_approval_list table td {
    vertical-align: top;
    border-right: 1px solid #ccc;
    border-bottom: 1px solid #ccc;
    padding: 1px;
}

.cc_pool_approval_list table th {
    border-bottom: 1px solid #444;
}

.em_code {
    color: #777;
    font-family: Courier New, serif;
}
.poster {
    float: right;
    text-align: right;
}
</style>
<form action="%(#post_url)%" method="post">
  <div class="cc_pool_approval_list">
    <table>
      <tr>%loop(#heads,head)%<th>%(#head)%</th>%end_loop%</tr>
      %loop(records,r)%
      <?= $extra = unserialize($r['pool_item_extra']); ?>
      <tr>
         <td><input type="radio" name="action[%(#r/pool_item_id)%]" value="approve" checked="checked" /></td>
         <td><input type="radio" name="action[%(#r/pool_item_id)%]" value="delete" /></td>
         <td><input type="radio" name="action[%(#r/pool_item_id)%]" value="nothing" /></td>
         <td><a href="%(#r/file_page_url)%">%(#r/upload_name)%</a>
            &nbsp;%text(str_by)%&nbsp;%(#r/user_real_name)% (%(#r/user_name)%)</td>
         <td>%(#r/pool_item_artist)%</td>
         <td><a href="%(#r/pool_item_url)%" target="_blank" >%(#r/pool_item_name)%</a>
         %if_not_null(#extra/ttype)%
             <br />
             <div class="poster"><?= _('Poster') ?>: <a href="mailto:%(#extra/email)%">%(#extra/poster)%</a></div>
             <i><?= _('Type') ?>: %(#extra/ttype)%</i>
             %if_not_null(#extra/embed)%
                <div class="em_code"><? print '<br />' . wordwrap( strtr($extra['embed'],$tr), 80, '<br />', true ); ?></div>
                <a href="javascript://doshowembed" class="embed_popup" id="id_%(#r/pool_item_id)%"><?= _('see embed') ?></a>
             %end_if%
             </td>
         %else%
             @ <a href="%(#r/pool_item_download_url)%" class="cc_pool_download_link">%(#r/pool_name)%</a>
             </td>
         %end_if%
      </tr>
      %end_loop%
    </table>
  </div>
  <input type="submit" value="Submit" />
</form>
