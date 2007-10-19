<?

function _t_file_macros_license_rdf($T,&$A)
{
    

}

function _t_file_macros_show_zip_dir($T,&$A)
{
    

    $R =& $_TV['record'];
    foreach( $R['zipdirs'] as $zip )
    {
        print "<p class=\"zipdir_title\">{$GLOBALS['str_zip_title']}</p>\n" .
              "<ul class=\"cc_zipdir\">\n";
        foreach( $zip['dir']['files'] as $F )
            print "<li>{$F}</li>\n";
        print "</ul>\n";
    }
}

function _t_file_macros_request_reviews($T,&$A)
{
    
?>
<div id="requested_reviews"></div>
<script>
//<!--
url = '<?= $_TV['record']['comment_thread_url'] ?>' + q + 'ajax=1';
new Ajax.Updater( 'requested_reviews', url, { method: 'get' } );
//-->
</script>
<?
}

function _t_file_macros_print_recent_reviews($T,&$A)
{
    

    print "<p class=\"recent_reviews\">{$GLOBALS['str_recent_reviews']}</p>\n" .
          "<ul id=\"recent_reviews\">\n";
    foreach( $_TV['posts'] as $post )
    {
        $text = CC_strchop($post['post_text'],50);
        print "<li><span class=\"poster_name\">{$post['username']}</span> <a href=\"{$post['post_url']}\">{$text}</a></li>\n";
    }
    print "</ul>\n";
    print "<a href=\"{$_TV['view_topic_url']}\">{$GLOBALS['str_read_all']}</a>\n";
}

function _t_file_macros_print_howididit_link($T,&$A)
{
    

    print "<a href\"{$_TV['record']['howididit_link']['action']}\">{$_TV['record']['howididit_link']['text']}</a><br \>\n";
}

function _t_file_macros_upload_not_published($T,&$A)
{
    

    print "<div class=\"unpublished\">{$_TV['record']['publish_message']}</div>";
}

function _t_file_macros_upload_banned($T,&$A) 
{
    

    print "<div class=\"upload_banned\">{$_TV['record']['banned_message']}</div>";
}

?>