<?if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

global $_TV;

_template_compat_required();
?><div >

<style >
table.statstable {
  border: 3px solid #559;
  background-color: #DDE;
  margin: 5px;
}

.shead {
  background-color: #559;
  color: white;
  padding: 2px 1px 2px 1px;
  }
</style>
<table >
<tr >
<td >
<ul >
<li ><a  href="<?= $_TV['root-url']?>/media/charts">Upload ranks (all-time)</a></li>
<li ><a  href="<?= $_TV['root-url']?>/media/charts/users">User ranks (all-time)</a></li>
<li ><a  href="<?= $_TV['root-url']?>/media/charts/users/date">Users latest</a></li>
<li ><a  href="<?= $_TV['root-url']?>/media/charts/upload/name">Uploads (alphabetical)</a></li>
<li ><a  href="<?= $_TV['root-url']?>/media/charts/users/name">Users (alphabetical)</a></li>
</ul>
</td>
<td >
<?if ( !empty($_TV['user_recs'])) {?><table  class="statstable">
<tr >
<th >Artist</th>
<th >Ratings</th>
<th >remixes</th>
<th >remixed</th>
<th >uploads</th>
</tr>
<?$carr101 = $_TV['user_recs'];$cc101= count( $carr101);$ck101= array_keys( $carr101);for( $ci101= 0; $ci101< $cc101; ++$ci101){    $_TV['R'] = $carr101[ $ck101[ $ci101 ] ];   ?><tr >
<td ><a  href="<?= $_TV['R']['artist_page_url']?>"><?= $_TV['R']['user_real_name']?></a></td>
<td ><?_template_call_template('charts.xml/ratings_dots');
?></td>
<td ><?= $_TV['R']['user_num_remixes']?></td>
<td ><?= $_TV['R']['user_num_remixed']?></td>
<td ><?= $_TV['R']['user_num_uploads']?></td>
</tr><?}?></table><?}if ( !empty($_TV['upload_recs'])) {?><table  class="statstable">
<tr >
<th >name</th>
<th >artist</th>
<th >ratings</th>
<th >remixes</th>
<th >sources</th>
</tr>
<?$carr102 = $_TV['upload_recs'];$cc102= count( $carr102);$ck102= array_keys( $carr102);for( $ci102= 0; $ci102< $cc102; ++$ci102){    $_TV['R'] = $carr102[ $ck102[ $ci102 ] ];   ?><tr >
<td ><a  href="<?= $_TV['R']['artist_page_url']?>"><?= $_TV['R']['user_real_name']?></a></td>
<td ><a  href="<?= $_TV['R']['file_page_url']?>"><?= $_TV['R']['upload_name']?></a></td>
<td  style="white-space: nowrap"><?_template_call_template('charts.xml/ratings_dots');
?></td>
<td ><?= $_TV['R']['upload_num_remixes']?></td>
<td ><?= $_TV['R']['upload_num_sources']?></td>
</tr><?}?></table><?}?></td>
</tr>
</table>
<?
function _t_charts_ratings_dots() {
   global $_TV;
if ( !empty($_TV['R']['ratings_score'])) {$carr103 = $_TV['R']['ratings'];$cc103= count( $carr103);$ck103= array_keys( $carr103);for( $ci103= 0; $ci103< $cc103; ++$ci103){    $_TV['i'] = $carr103[ $ck103[ $ci103 ] ];   ?><img  src="<?= $_TV['root-url']?>/ccimages/stars/dot-<?= $_TV['i']?>.gif" height="10" width="10" /><?}}if ( !empty($_TV['R']['ratings_score'])) {?><span >(<?= $_TV['R']['ratings_score']?>)</span><?}}?></div>