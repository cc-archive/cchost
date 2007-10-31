<head> 

%if_empty(page-caption)%
  <title>%(site-title)% - %(site-description)%</title>
%else%
  <title>%(site-title)% - %text(page-caption)%</title>
%end_if%
 
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

</head>