<?

$T->Call('reviews_recent_edit');

?><h3><?= $T->String('str_reviews_most_recent'); ?></h3><?

cc_query_fmt('f=html&noexit=1&nomime=1&t=reviews_browse&datasource=topics');

?>