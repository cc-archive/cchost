<?/*
[meta]
    type = feed
    desc = _('RSS 2.0 Feed')
    dataview = rss
    embedded = 1
[/meta]
[dataview]
function rss_dataview()
{
    $ccf = ccl('files') . '/';
    $TZ = ' ' . CCUtil::GetTimeZone();
    $avatar_sql = cc_get_user_avatar_sql();

    // Thu, 27 Dec 2007 09:28:38 PST
    // %a,  %d %b %Y    %T

    $sql =<<<EOF
        SELECT $avatar_sql, upload_id, upload_name, upload_name, upload_contest, user_name, user_real_name,
        CONCAT( '$ccf', user_name, '/', upload_id ) as file_page_url, 
        upload_tags, license_url,
        upload_description as format_text_upload_description,
        upload_description as format_html_upload_description,
        CONCAT( DATE_FORMAT(upload_date,'%a, %d %b %Y %T'), '$TZ' ) as rss_pubdate
        %columns%
        FROM cc_tbl_uploads
        JOIN cc_tbl_user ON upload_user=user_id
        JOIN cc_tbl_licenses ON upload_license=license_id
        %joins%
        %where%
        %order%
        %limit%
EOF;

    return array(   'sql' => $sql,
                    'e' => array(
                            CC_EVENT_FILTER_DOWNLOAD_URL,
                            CC_EVENT_FILTER_FORMAT,
                            ) );
}
[/dataview]
*/

print '<?xml version="1.0" encoding="utf-8" ?>' 
?>

<rss version="2.0" 
   xmlns:content="http://purl.org/rss/1.0/modules/content/"
   xmlns:cc="http://web.resource.org/cc/"   
   xmlns:dc="http://purl.org/dc/elements/1.1/"
   xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"
   xmlns:media="http://search.yahoo.com/mrss/"
   xmlns:atom="http://www.w3.org/2005/Atom"
   >
<channel>
<title><?= $A['channel_title']?></title>
<link><?= $A['home-url']?></link>
<description><?= $A['channel_description']?></description>
<language><?= $A['lang_xml']?></language>
<pubDate><?= $A['rss-pub-date']?></pubDate>
<lastBuildDate><?= $A['rss-build-date']?></lastBuildDate>
<atom:link href="<?= $A['feed_url']?>" rel="self" type="application/rss+xml" />
<? if( !empty($A['logo']) ) { ?>
<image> 
			<url><?= ccd($A['logo']['src']) ?></url>
			<link><?= $A['home-url']?></link> 
			<title><?= $A['channel_title']?></title> 
			<height><?= $A['logo']['h'] ?></height>
			<width><?= $A['logo']['w'] ?></width>
</image>
<?
}

if ( !empty($A['records'])) {

    foreach( $A['records'] as $item )
    { 
?>
    <item>
        <title><?= $item['upload_name']?></title>
        <link><?= $item['file_page_url']?></link>
        <pubDate><?= $item['rss_pubdate']?></pubDate>
        <dc:creator><?= $item['user_real_name']?></dc:creator>
        <description><?= $item['upload_description_plain']?> </description>
        <content:encoded><![CDATA[<?= $item['upload_description_html'] ?>]]></content:encoded>
        <enclosure url="<?= $item['files']['0']['download_url']?>" length="<?= $item['files']['0']['file_rawsize']?>" type="<?= $item['files']['0']['file_format_info']['mime_type']?>"></enclosure>
        <?
        $tags = split(',',$item['upload_tags']);
        foreach( $tags as $tag )
        { 
            if( !empty($tag) )
            {
                ?><category><?= $tag?></category><?
            }
        }
?>

        <guid><?= $item['file_page_url']?></guid>
        <cc:license><?= $item['license_url'] ?></cc:license>
        <media:thumbnail url="<?= $item['user_avatar_url']?>"></media:thumbnail>
    </item>
<?
    }
}
  
?></channel>
</rss>