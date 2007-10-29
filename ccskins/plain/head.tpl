
%macro(html_head)%
    %if_not_empty(ajax)%
        %return%
    %end_if%
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 //EN">
<head> 
<title>%(site-title)% - %var_check(page-title)%</title>

%if_not_empty(site-meta-keywords)%
    <meta name="keywords" content="%(site-meta-keywords)%" />
%end_if%
%if_not_empty(site-meta-description)%
    <meta name="description" content="%(site-meta-description)%" />
%end_if%

<meta name="robots" content="index, follow" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script>
//<!--
var home_url = '%(home-url)%';
var root_url = '%(root-url)%';
var q        = '%(q)%';
//-->
</script>

    %call(print_head_links)%

</head>
%end_macro%

%macro(print_head_links)%
    %loop(head_links,head)%
        <link rel="%(#head/rel)%" type="%(#head/type)%" href="%(#head/href)%" title="%(#head/title)%"/>
    %end_loop%

    %loop(style_sheets,css)%
        <link rel="stylesheet" type="text/css" href="%url(#css)%" title="Default Style" />
    %end_loop%

    %if_not_empty(script_links)%
        <? page_script_link_helper($A['script_links'],$T); ?>
    %end_if%

    %loop(script_blocks,script_block)%
        %call(#script_block)%
    %end_loop%
%end_macro%

<?
function page_script_link_helper($links,$T)
{
    foreach( $links as $script_link ) {
        if( substr($script_link,0,7) == 'http://' )
            $path = $script_link;
        else {
            $path = $T->Search($script_link);
            if( empty($path) ) die( "Can't find script '$script_link'" );
            $path = ccd($path);
        }
        print "<script type=\"text/javascript\" src=\"${path}\" ></script>";
    }
}
?>