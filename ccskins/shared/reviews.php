<?


if( empty($_GET['offset']) )
{
    $T->Call('reviews_recent_edit');
    $offset = 0;
}
else
{
    $offset = $_GET['offset'];
}

?><h3><?= $T->String('str_reviews_most_recent'); ?></h3><?

cc_query_fmt('f=html&noexit=1&nomime=1&sort=date&ord=desc&t=reviews_browse&datasource=topics&paging=1&limit=30&offset=' . $offset);

$T->Call('prev_next_links');
?>
