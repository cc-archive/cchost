<?/*
[meta]
    type = extras
    desc = _('Artist Spotlight')
[/meta]

*/

$sql  = 'SELECT topic_name FROM cc_tbl_topics WHERE topic_type=\'artist_qa\' ORDER by topic_date DESC LIMIT 5';
$rows = CCDatabase::QueryRows($sql);
$sql  = 'SELECT COUNT(*) FROM cc_tbl_topics WHERE topic_type=\'artist_qa\'';
$max  = CCDatabase::QueryItem($sql);
?>
<p>Artists' Spotlight</p>
<ul>
<? foreach( $rows as $row ) { ?>
    <li><a href="<?= ccl('artist-spotlight-q-a?offset=') . --$max ?>"><?= $row['topic_name'] ?></a></li>
<? } ?>
</ul>

