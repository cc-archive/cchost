<?

error_reporting(E_ALL);

CCEvents::AddHandler(CC_EVENT_APP_INIT,'fix_topics');

function fix_topics()
{
    if( empty($_GET['fix']) )
        return;

    $sql =<<<EOF
UPDATE `cc_tbl_forum_threads`  SET forum_thread_name = (
SELECT topic_name
FROM cc_tbl_topics
WHERE topic_id = forum_thread_oldest
)
EOF;

    $rcount = 0;
    $rbcount = 0;
    CCDatabase::Query('UPDATE cc_tbl_topics SET topic_left = 0, topic_right = 0');
    $qr = CCDatabase::Query('SELECT topic_id, topic_upload, topic_thread FROM cc_tbl_topics WHERE topic_type <> \'reply\' ORDER BY topic_date ASC');
    $right = 0;
    while( $_tid = mysql_fetch_row($qr) )
    {
        if( ++$rcount % 1000 == 0 )
            print("Count: $rcount\n<br />");
        $right = rebuild_tree($_tid[0],$_tid[1],$right);
    }

    exit;
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


    $sql =<<<EOF

// EXISTING CHILDREN
SELECT @myRight := rgt FROM nested_category
WHERE name = 'TELEVISIONS';
UPDATE nested_category SET rgt = rgt + 2 WHERE rgt > @myRight;
UPDATE nested_category SET lft = lft + 2 WHERE lft > @myRight;
INSERT INTO nested_category(name, lft, rgt) VALUES('GAME CONSOLES', @myRight + 1, @myRight + 2);


// FIRST CHILD
SELECT @myLeft := lft FROM nested_category
WHERE name = '2 WAY RADIOS';
UPDATE nested_category SET rgt = rgt + 2 WHERE rgt > @myLeft;
UPDATE nested_category SET lft = lft + 2 WHERE lft > @myLeft;
INSERT INTO nested_category(name, lft, rgt) VALUES('FRS', @myLeft + 1, @myLeft + 2);

EOF;

?>