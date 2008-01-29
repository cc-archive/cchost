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
SELECT  topic_id, topic_type, topic_name, topic_date
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
<style type="text/css">
.odd_row {
    background-color: #FDD;
}
table.cc_content_pages,
table.cc_content_topics {
    margin: 9px;
}

table.cc_content_pages  td,
table.cc_content_topics td {
    padding: 2px 15px 2px 3px;
}
#topic_picker {
    margin: 8px;
}
</style>
<h2><?= _('Conent Pages') ?></h2>
<a href="%(home-url)%admin/content/page" class="cc_gen_command"><span><?= _('Create a new page')?></span></a>
<table class="cc_content_pages">
%loop(content_pages,cp)%
<? $class = $i_cp & 1 ? 'odd_row' : 'even_row'; ?>
<tr class="%(#class)%" >
    <td>%(#cp)%</td>
    <td><a href="%(home-url)%admin/content/page/edit/%(q)%page=%(#k_cp)%">edit</a></td>
    <td><a href="%(home-url)%admin/content/page/delete/%(q)%page=%(#k_cp)%">delete</a></td>
    <? $page = preg_replace('/\.[^\.]+$/','',basename($k_cp)); ?>
    <td><a target="_blank" href="%(home-url)%docs/%(#page)%">view</a></td>
</tr>
%end_loop%
</table>
<h2><?= _('Content Topics') ?></h2>
<a href="%(home-url)%admin/content/post" class="cc_gen_command"><span><?= _('Create new content')?></span></a>
<div id="topic_picker"><?= _('Select topic type')?>: <select id="topic_types" onchange="window.location.href = '<?= cc_current_url() ?>' + q + 'topic_type=' + this.options[this.selectedIndex].value;">
%loop(topic_types,tt)%
<option value="%(#tt)%" <?= ($tt == $_GET['topic_type']) ? 'selected="selected"' : '' ?>>%(#tt)%</option>
%end_loop%
</select></div>
<table class="cc_content_topics" cellspacing="0" cellspacing="0" >
%loop(records,R)%
<? $class = $i_R & 1 ? 'odd_row' : 'even_row'; ?>
<tr class="%(#class)%">
    <td>#%(#R/topic_id)%</td>
    <td>%(#R/topic_date)%</td>
    <td>%(#R/topic_name)%</td>
    <td>%(#R/topic_type)%</td>
    <td><a href="%(home-url)%admin/content/edit/%(#R/topic_id)%">edit</a></td>
    <td><a href="%(home-url)%admin/content/delete/%(#R/topic_id)%">delete</a></td>
</tr>
%end_loop%
</table>
%call(prev_next_links)%