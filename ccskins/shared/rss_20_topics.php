<?/*
[meta]
    type = feed
    desc = _('RSS 2.0 Feed for Topics')
    formats = rss
    datasource = topics
    dataview = rss_topic
    embedded = 1
[/meta]
[dataview]
function rss_topic_dataview($queryObj)
{
    $TZ = ' ' . CCUtil::GetTimeZone();


    $ccr = ccl('reviews') . '/';
    $cct = ccl('thread') . '/';
    $cctopic = ccl('topics','view') . '/';

    // NOTE add to this list when you get to blogs, etc.
    if( empty($queryObj->args['page']) )
    {
        if( !empty($queryObj->args['thread']) )
        {
            $ccp_sql = "CONCAT('{$cct}',topic_thread, '#', topic_id)";
        }
        else
        {
            // this is bogus.. but we may be stuck in the case of replies to reviews (?)
            $ccp_sql = "CONCAT('{$cctopic}',topic_id)";
        }
    }
    else
    {
        $slug = cc_get_topic_name_slug();
        $ccp = url_args(ccl($queryObj->args['page']),'topic=');
        $ccp_sql = "CONCAT('{$ccp}', {$slug} )";
    }

    // Thu, 27 Dec 2007 09:28:38 PST
    // %a,  %d %b %Y    %T

    $Y = date('Y') + 1;

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
            IF( topic_type = 'forum',
              CONCAT('{$cct}',topic_thread, '#', topic_id),
              {$ccp_sql}              
              )
          ) as topic_permalink
        FROM cc_tbl_topics
        JOIN cc_tbl_user author ON topic_user=author.user_id
        LEFT OUTER JOIN cc_tbl_forum_threads ON topic_thread=forum_thread_id
        LEFT OUTER JOIN cc_tbl_forums ON forum_thread_forum=forum_id
        LEFT OUTER JOIN cc_tbl_uploads ups ON topic_upload=upload_id
        LEFT OUTER JOIN cc_tbl_user reviewee ON ups.upload_user=reviewee.user_id
        %joins%
        %where% and (topic_date < '${Y}') AND (topic_deleted = 0)
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
   xmlns:cc="http://creativecommons.org/ns#"   
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
      <? if( !empty($item['enclosure_url']) ) { ?>
         <enclosure url="<?= $item['enclosure_url']?>" length="<?= $item['enclosure_size']?>" type="<?= $item['enclosure_type']?>"></enclosure>
      <? } ?>
    </item>
    <?
        } }
    ?>
  </channel>
</rss>
