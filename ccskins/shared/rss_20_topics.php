<?/*
[meta]
    type = feed
    desc = _('RSS 2.0 Feed for Topics')
    dataview = rss_topic
    embedded = 1
[/meta]
[dataview]
function rss_topic_dataview()
{
    $TZ = ' ' . CCUtil::GetTimeZone();

    // NOTE add to this list when you get to blogs, etc.

    $ccr = ccl('reviews') . '/';
    $cct = ccl('thread') . '/';

    // Thu, 27 Dec 2007 09:28:38 PST
    // %a,  %d %b %Y    %T

    $sql =<<<EOF
        SELECT topic_date, author.user_real_name, 
            topic_text as format_text_topic_text, 
            topic_text as format_html_topic_text,
        CONCAT( DATE_FORMAT(topic_date,'%a, %d %b %Y %T'), '$TZ' ) as rss_pubdate,
        IF( LENGTH(forum_name) > 0,
            CONCAT( forum_name, ' :: ', IF( LENGTH(topic_name) > 0, topic_name, forum_thread_name) ),
            topic_name ) as topic_name,
        IF( topic_type = 'review', 
            CONCAT('{$ccr}',reviewee.user_name,'/',topic_upload,'#',topic_id),
            CONCAT('{$cct}',topic_thread, '#', topic_id) ) as topic_permalink
        FROM cc_tbl_topics
        JOIN cc_tbl_user author ON topic_user=user_id
        LEFT OUTER JOIN cc_tbl_forum_threads ON topic_thread=forum_thread_id
        LEFT OUTER JOIN cc_tbl_forums ON forum_thread_forum=forum_id
        LEFT OUTER JOIN cc_tbl_uploads ups ON topic_upload=upload_id
        LEFT OUTER JOIN cc_tbl_user reviewee ON ups.upload_user=reviewee.user_id
        %joins%
        %where%
        %order%
        %limit%
EOF;

    return array(   'sql' => $sql,
                    'e' => array(
                            CC_EVENT_FILTER_FORMAT,
                            ) );
}
[/dataview]
*/

print '<?xml version="1.0" encoding="utf-8" ?>' 
?>
<rss version="2.0" 
   xmlns:content="http://purl.org/rss/1.0/modules/content/"
   xmlns:cc="http://backend.userland.com/creativeCommonsRssModule"   
   xmlns:dc="http://purl.org/dc/elements/1.1/"
   xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
   >
  <channel>
    <title><?= $A['channel_title'] ?></title>
    <link><?= $A['home-url'] ?></link>
    <description><?= $A['channel_description'] ?></description>
    <language>en-us</language>

    <pubDate><?= $A['rss-pub-date'] ?></pubDate>
    <lastBuildDate><?= $A['rss-build-date'] ?></lastBuildDate>
    <?
        if( !empty($A['records']) ) { foreach( $A['records'] as $item ) {
    ?>
    <item>
      <title><?= $item['topic_name'] ?></title>
      <link><?= $item['topic_permalink'] ?></link>
      <pubDate><?= $item['rss_pubdate'] ?></pubDate>
      <dc:creator><?= $item['user_real_name'] ?></dc:creator>
      <description><?= $item['topic_text_plain'] ?></description>
      <content:encoded><![CDATA[<?= $item['topic_text_html'] ?>]]></content:encoded>
      <guid><?= $item['topic_permalink'] ?></guid>
      <cc:license><?= $A['topics_license_url'] ?></cc:license>
    </item>
    <?
        } }
    ?>
  </channel>
</rss>