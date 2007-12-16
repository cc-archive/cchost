<?

fix_collabs();
fix_topics();

function fix_topics()
{
    print("Topic tree updating...");

    $sql =<<<EOF
UPDATE `cc_tbl_forum_threads`  SET forum_thread_name = (
SELECT topic_name
FROM cc_tbl_topics
WHERE topic_id = forum_thread_oldest
)
EOF;
    CCDatabase::Query($sql);

    CCDatabase::Query('UPDATE cc_tbl_topics SET topic_left = 0, topic_right = 0');
    $qr = CCDatabase::Query('SELECT topic_id, topic_upload, topic_thread FROM cc_tbl_topics WHERE topic_type <> \'reply\' ORDER BY topic_date ASC');
    $right = 0;
    while( $_tid = mysql_fetch_row($qr) )
    {
        $right = rebuild_tree($_tid[0],$_tid[1],$right);
    }

    print("done<br />");
}

function rebuild_tree($parent, $upload, $left) {
   // the right value of this node is the left value + 1
   $right = $left+1;

   // get all children of this node
   $result = mysql_query( "SELECT topic_tree_child FROM cc_tbl_topic_tree WHERE topic_tree_parent=$parent") or die(mysql_error());
   while ($row = mysql_fetch_array($result)) {
       $right = rebuild_tree($row['topic_tree_child'], $upload, $right);
   }

   // we've got the left value, and now that we've processed
   // the children of this node we also know the right value
   mysql_query("UPDATE cc_tbl_topics SET topic_left=$left, topic_right=$right, topic_upload = $upload WHERE topic_id=$parent" )
       or die(mysql_error());

   // return the right value of this node + 1
   return $right+1;
} 


function fix_collabs()
{
    $sql =<<<EOF
    SELECT upload_id,upload_tags
        FROM cc_tbl_collab_uploads
        JOIN cc_tbl_uploads ON upload_id=collab_upload_upload
EOF;
    $qr = CCDatabase::Query($sql);
    while( list($id,$tags)= mysql_fetch_row($qr) )
    {
        if( preg_match('/,(remix|acappella|sample),/',$tags,$m) )
        {
            $type = $m[1];
        }
        else
        {
            if( !preg_match('/$,([^,]+),/',$tags,$m) )
                continue; // this should really never frakin happen
            $type = $m[1];
        }
        $sql = "UPDATE cc_tbl_collab_uploads SET collab_upload_type = '{$type}' WHERE collab_upload_upload = {$id}";
        CCDatabase::Query($sql);
    }

    print('Collaboration type field updated<br />'."\n");
}

?>