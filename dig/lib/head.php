<?
/*
* Artistech Media has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file LICENSE.TXT.
* 
* You may use dig.ccMixter software in accordance with the
* terms of that license. You agree that you are solely 
* responsible for your use of dig.ccMixter software and you
* represent and warrant to Artistech Media that your use
* of dig.ccMixter software will comply with the CC-GNU-GPL.
*
* $Id$
*
*/

foreach( array('home', 'dig', 'featured', 'about') as $P )
{
    $var = $P . '_class';
    if( !isset($$var) )
        $$var = '';
}
if( !isset($page_title) )
{
    die('\$page_title must be set to something before using this include.');
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <!-- Designed by nvzion.com, 2010 -->
    <title><?= $page_title ?></title>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8">
    <meta name="MSSmartTagsPreventParsing" content="true" />
    <meta name="description" content="Description goes here." />
    <meta name="keywords" content="keywords, go, here" />
    <link rel="stylesheet" href="css/site.css" type="text/css" media="screen, projection" />
    <link rel="stylesheet" href="css/print.css" type="text/css" media="print" />
    <!--[if IE]><link rel="stylesheet" href="css/ie.css" type="text/css" media="screen, projection" /><![endif]-->
    <!--[if IE 6]><link rel="stylesheet" href="css/ie6.css" type="text/css" media="screen, projection" /><![endif]-->
    <script src="js/js_config.php" type="text/javascript" charset="utf-8"></script>
    <script src="js/dd_belatedpng.js" type="text/javascript" charset="utf-8"></script>
    <script src="js/jquery.js" type="text/javascript" charset="utf-8"></script>
    <script src="js/plugins/jquery.simplemodal.js" type="text/javascript" charset="utf-8"></script>
    <script src="http://mediaplayer.yahoo.com/js" type="text/javascript" charset="utf-8"></script>
    <script src="js/ccm-query.js" type="text/javascript" charset="utf-8"></script>
    <script src="js/ccmd.js" type="text/javascript" charset="utf-8"></script>
<?
    if( !empty($script_heads) )
    {
        foreach( $script_heads as $S )
            print $S;
    }
?>
</head>
<body>
  <div class="container">
    <div id="header">
        <h1>dig.ccmixter <? if(!empty($page_title)) { print $page_title; } ?></h1>
    </div>
    <div id="nav">
        <ul>
            <li><a href="/"        <?= $home_class ?>>home</a></li>
            <li><a href="dig"      <?= $dig_class ?>>dig</a></li>
            <li><a href="featured" <?= $featured_class ?>>featured</a></li>
            <li><a href="about"    <?= $about_class ?>>about</a></li>
        </ul>
        <div class="clearer"></div>
    </div>