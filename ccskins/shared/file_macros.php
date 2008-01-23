<?

function _t_file_macros_print_howididit_link(&$T,&$A)
{
    ?><a href="<?= ccl('howididit',$A['record']['upload_id']) ?>"><?= $T->String('str_how_i_did_it') ?></a><br /><?
}


function _t_file_macros_license_rdf(&$T,&$A)
{
}

function _t_file_macros_show_nsfw(&$T,&$A)
{
    print '<p id="nsfw">' . $T->String(array('str_nsfw_t','<a href="http://en.wikipedia.org/wiki/NSFW">','</a>')) . '</p>';
}


function _t_file_macros_show_zip_dir(&$T,&$A)
{
    $R =& $A['record'];
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

function _t_file_macros_request_reviews(&$T,&$A)
{
    ?><div id="requested_reviews"><?
        cc_query_fmt('noexit=1&nomime=1&f=html&t=reviews_preview&sort=topic_date&ids=' . $A['record']['upload_id'] );
    ?></div><?
}

function _t_file_macros_print_recent_reviews(&$T,&$A)
{
    ?>
        <p class="recent_reviews"><?= $T->String('str_recent_reviews') ?></p>
          <ul id="recent_reviews">
    <?
    foreach( $A['posts'] as $post )
    {
        $text = CC_strchop($post['post_text'],50);
        print "<li><span class=\"poster_name\">{$post['username']}</span> <a href=\"{$post['post_url']}\">{$text}</a></li>\n";
    }
    ?></ul>
    <a href="<?= $A['view_topic_url'] ?>"><?= $T->String('str_read_all') ?></a><?
}

function _t_file_macros_upload_not_published(&$T,&$A)
{
    print "<div class=\"unpublished\">{$A['record']['publish_message']}</div>";
}

function _t_file_macros_upload_banned(&$T,&$A) 
{
    print "<div class=\"upload_banned\">{$A['record']['banned_message']}</div>";
}

?>