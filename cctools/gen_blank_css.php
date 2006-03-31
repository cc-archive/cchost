<?
/*
* Creative Commons has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file LICENSE.TXT.
* 
* You may use the ccHost software in accordance with the
* terms of that license. You agree that you are solely 
* responsible for your use of the ccHost software and you
* represent and warrant to Creative Commons that your use
* of the ccHost software will comply with the CC-GNU-GPL.
*
* $Header$
*
*/
if( isset($_SERVER['REMOTE_ADDR']) )
  exit;

error_reporting(E_ALL);
chdir('..');


$out_file = "cctemplates/skin-blank.css";
//$out_file = 'cctemplates/temp-upload.css';

$files = array( 
                "cctemplates/comments.xml",
                "cctemplates/contest.xml",
                "cctemplates/custom.xml",
                "cctemplates/form.xml",
                "cctemplates/license.xml",
                "cctemplates/misc.xml",
                "cctemplates/navigator.xml",
                "cctemplates/page.xml",
                "cctemplates/pool.xml",
                "cctemplates/remix_form.xml",
                "cctemplates/tags.xml",
                "cctemplates/topics.xml",
                "cctemplates/upload.xml",
                "cctemplates/upload_misc.xml",
                "cctemplates/upload_quick.xml",
                "cctemplates/user.xml",
                );

$a = array();
$ids = array();

foreach( $files as $file )
{
    $str = file_get_contents($file);
    preg_match_all('/class="([^"\$]+)"/',$str,$m);
    $a = array_merge( $a, array_unique($m[1]) );
    preg_match_all('/\s+id="([^"\$]+)"/',$str,$m);
    $ids = array_merge( $ids, array_unique($m[1]) );

}

$a = array_unique($a);
asort($a);
$ids = array_unique($ids);
asort($ids);
$date = date('Y-m-d');
$css =<<<END

/*
   \$Header$
   generated on $date
*/


/* GENERIC ELEMENTS */

body
{
}

body, input, td, select
{
}

img, table
{
	border-width: 0px;
}

input[type=text], textarea
{
}

input[type=submit]
{
}

textarea
{
}

a, a:visited
{
}


/* SELECTORS GENERATED BY CODE */

.cc_system_prompt {
}

.cc_body_text {
}

.cc_contest_open {
}

.cc_contest_closed {
}

.cc_contest_voting_status {
}

.cc_form_error_input {
}

.cc_date_field {
}

.cc_license_image {
}

/* These selectors are pre-filled in for convenience only */

input.cc_form_input {
    width: 300px;
}

input.cc_form_input_short {
	width: 40px;
}

.cc_form_submit_message {
    height: 350px;
    border: 2px solid #666;
    padding: 30% 20px 0px 20px;
}
.cc_user_admin_link {
    display: block;
    border: 1px solid pink;
    background: white;
    padding: 3px;
}

.cc_user_admin_link a, .cc_user_admin_link a:visited
{
   color: brown;
}

.cc_php_error_message, .cc_system_error_message {
    display: block;
    padding: 5px;
    background-color: white;
    font-size: 12px;
    font-weight: bold;
    color: red;
    font-family: Courier New, courier, serif;
    border: 1px solid red;
    text-align: center;
}

.cc_system_error_message {
    font-family: arial, sans serif;
}


a.cc_external_link  {
    background-image: url('../ccimages/remote.gif');
	background-repeat: no-repeat;
	background-position: right;
    padding-right: 20px;
}

.cc_feed_button, .cc_feed_button:visited
{
	background: url('../ccimages/feed-icon16x16.png') no-repeat top left;
	height: 19px;
	padding-left: 18px;
	margin: 0px;
	font-weight: bold;
	font-variant: small-caps;
    color: #888;
	font-size: 12px;
}

.cc_ratings span {
  vertical-align: top;
}

.cc_ratings_on
{
	background-image: url('../ccimages/star1.gif');
	padding-left: 14px;
	background-repeat: no-repeat;
}

.cc_ratings_off
{
	background-image: url('../ccimages/star.gif');
	padding-left: 14px;
	background-repeat: no-repeat;
}

/* Empty selectors.... */

END;


foreach( $a as $selector )
{
    $css .= ".$selector {\n\n}\n\n";
}

foreach( $ids as $id )
{
    $css .= "#$id {\n\n}\n\n";
}

print($css);
$f = fopen($out_file,'w+');
fwrite($f,$css,strlen($css));
fclose($f);

?>