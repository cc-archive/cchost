<?/*
[meta]
    type = template_component
    dataview = content_manage
    embedded = 1
[/meta]
[dataview]
function content_manage_dataview() 
{
    $sql =<<<EOF
SELECT  topic_id, topic_type, topic_name
FROM cc_tbl_topics AS topic
%where% 
%order%
%limit%
EOF;

    $sql_count =<<<EOF
SELECT COUNT(*)
FROM cc_tbl_topics AS topic
%where%
EOF;
    return array( 'sql' => $sql,
                  'sql_count' => $sql_count,
                   'e'  => array()
                );
}
[/dataview]
*/
if( empty($_GET['topic_type']) )
    $_GET['topic_type'] = '';
?>
<h2><?= _('Conent Pages') ?></h2>
<a href="%(home-url)%admin/content/page"><?= _('Create a new page')?></a>
<table class="cc_content_pages">
%loop(content_pages,cp)%
<tr>
    <td>%(#cp)%</td>
    <td><a href="%(home-url)%admin/content/page/edit/%(q)%page=%(#k_cp)%">edit</a></td>
    <td><a href="%(home-url)%admin/content/page/delete/%(q)%page=%(#k_cp)%">delete</a></td>
    <? $page = preg_replace('/\.[^\.]+$/','',basename($k_cp)); ?>
    <td><a target="_blank" href="%(home-url)%docs/%(#page)%">view</a></td>
</tr>
%end_loop%
</table>
<h2><?= _('Content Topics') ?></h2>
<a href="%(home-url)%admin/content/post"><?= _('Create new content')?></a>
<div><?= _('Select topic type')?>: <select id="topic_types" onchange="window.location.href = '<?= cc_current_url() ?>' + q + 'topic_type=' + this.options[this.selectedIndex].value;">
%loop(topic_types,tt)%
<option value="%(#tt)%" <?= ($tt == $_GET['topic_type']) ? 'selected="selected"' : '' ?>>%(#tt)%</option>
%end_loop%
</select></div>
<table class="cc_content_topics" cellspacing="0" cellspacing="0" >
%loop(records,R)%
<tr>
    <td>#%(#R/topic_id)%</td>
    <td>%(#R/topic_name)%</td>
    <td>%(#R/topic_type)%</td>
    <td><a href="%(home-url)%admin/content/edit/%(#R/topic_id)%">edit</a></td>
    <td><a href="%(home-url)%admin/content/delete/%(#R/topic_id)%">delete</a></td>
</tr>
%end_loop%
</table>
%call(prev_next_links)%