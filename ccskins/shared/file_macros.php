<?

function _t_file_macros_license_rdf($T,&$_TV)
{
}

function _t_file_macros_show_zip_dir($T,&$_TV)
{
    $R =& $_TV['record'];
    foreach( $R['zipdirs'] as $zip )
    {
        ?><p class="zipdir_title"><?= $T->String('str_zip_title') ?>: <span><?= $zip['name'] ?></span></p>
            <ul class="cc_zipdir"><?
        foreach( $zip['dir']['files'] as $F )
        {
            ?><li><?=$F?></li><?
        }
        ?></ul><?
    }
}

function _t_file_macros_request_reviews($T,&$A)
{
    ?><div id="requested_reviews"><?
        cc_query_fmt('noexit=1&nomime=1&f=html&t=reviews_preview&sort=topic_date&ids=' . $A['record']['upload_id'] );
    ?></div><?
}

function _t_file_macros_print_recent_reviews($T,&$_TV)
{
    ?><p class="recent_reviews"><?= $T->String('str_recent_reviews') ?></p>
          <ul id="recent_reviews"><?
    foreach( $_TV['posts'] as $post )
    {
        $text = CC_strchop($post['post_text'],50);
        print "<li><span class=\"poster_name\">{$post['username']}</span> <a href=\"{$post['post_url']}\">{$text}</a></li>\n";
    }
    ?></ul>
    <a href="<?= $_TV['view_topic_url'] ?>"><?= $T->String('str_read_all') ?></a><?
}

function _t_file_macros_print_howididit_link($T,&$_TV)
{
    print "<a href\"{$_TV['record']['howididit_link']['action']}\">{$_TV['record']['howididit_link']['text']}</a><br \>\n";
}

function _t_file_macros_upload_not_published($T,&$_TV)
{
    print "<div class=\"unpublished\">{$_TV['record']['publish_message']}</div>";
}

function _t_file_macros_upload_banned($T,&$_TV) 
{
    print "<div class=\"upload_banned\">{$_TV['record']['banned_message']}</div>";
}

?>