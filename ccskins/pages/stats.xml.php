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
  float:left;
}

.shead {
  background-color: #559;
  color: white;
  padding: 2px 1px 2px 1px;
  }
</style>
<?$_TV['total_uploads'] = cc_stats_total_uploads();$_TV['total_remixes'] = cc_stats_total_uploads('remix');$_TV['total_pells'] = cc_stats_total_uploads('acappella');$_TV['total_orgs'] = cc_stats_total_uploads('original');$_TV['total_samps'] = cc_stats_total_uploads('sample');$_TV['rtotal_pells'] = cc_stats_percent_remixed('acappella');$_TV['rtotal_orgs'] = cc_stats_percent_remixed('original');$_TV['rtotal_samps'] = cc_stats_percent_remixed('sample');?><table  class="statstable" style="float:left">
<tr ><th  class="shead" colspan="3">Overall Stats</th></tr>
<tr ><th ></th><th ></th><th >Remixed</th></tr>
<tr ><td >Total uploads:</td><td ><?= $_TV['total_uploads']?></td><td ></td></tr>
<tr ><td >Remixes:</td><td ><?= $_TV['total_remixes']?></td><td ></td></tr>
<tr ><td >A Cappellas:</td><td ><?= $_TV['total_pells']?></td><td ><?= $_TV['rtotal_pells']['percent']?>%</td></tr>
<tr ><td >Samples:</td><td ><?= $_TV['total_samps']?></td><td ><?= $_TV['rtotal_samps']['percent']?>%</td></tr>
<tr ><td >Fully Mixed:</td><td ><?= $_TV['total_orgs']?></td><td ><?= $_TV['rtotal_orgs']['percent']?>%</td></tr>
</table>
<table  class="statstable">
<tr ><th  class="shead" colspan="3">Most Sampled Artists</th></tr>
<tr ><th >Aritst</th><th >Sampled</th></tr>
<?$carr101 = cc_stats_most_remixed();$cc101= count( $carr101);$ck101= array_keys( $carr101);for( $ci101= 0; $ci101< $cc101; ++$ci101){    $_TV['smpld'] = $carr101[ $ck101[ $ci101 ] ];   ?><tr ><td ><a  class="cc_user_link" href="<?= $_TV['smpld']['artist_page_url']?>"><?= $_TV['smpld']['user_real_name']?></a></td>
<td ><?= $_TV['smpld']['num_remixed']?></td></tr>
<?}?></table>
<table  class="statstable">
<tr ><th  class="shead" colspan="2">Uploads by Month</th></tr>
<tr ><th >Month</th><th >Uploads</th></tr>
<?$carr102 = cc_stats_uploads_by_month();$cc102= count( $carr102);$ck102= array_keys( $carr102);for( $ci102= 0; $ci102< $cc102; ++$ci102){    $_TV['mostat'] = $carr102[ $ck102[ $ci102 ] ];   ?><tr ><td ><?= $_TV['mostat']['month']?>:</td><td ><?= $_TV['mostat']['c']?></td></tr>
<?}?></table>
<table  class="statstable">
<tr ><th  class="shead" colspan="2">Signups by Month</th></tr>
<tr ><th >Month</th><th >Signups</th></tr>
<?$carr103 = cc_stats_signups_by_month('2004-09');$cc103= count( $carr103);$ck103= array_keys( $carr103);for( $ci103= 0; $ci103< $cc103; ++$ci103){    $_TV['mostat'] = $carr103[ $ck103[ $ci103 ] ];   ?><tr ><td ><?= $_TV['mostat']['month']?>:</td><td ><?= $_TV['mostat']['c']?></td></tr>
<?}?></table>
<table  class="statstable">
<tr ><th  class="shead" colspan="2">Most Remixes</th></tr>
<tr ><th >Artist</th><th >Remixes</th></tr>
<?$carr104 = cc_stats_most_of_type('remix');$cc104= count( $carr104);$ck104= array_keys( $carr104);for( $ci104= 0; $ci104< $cc104; ++$ci104){    $_TV['a'] = $carr104[ $ck104[ $ci104 ] ];   ?><tr ><td ><a  href="<?= $_TV['a']['artist_page_url']?>" class="cc_user_link"><?= $_TV['a']['user_real_name']?></a></td><td ><?= $_TV['a']['c']?></td></tr>
<?}?></table>
<table  class="statstable">
<tr ><th  class="shead" colspan="2">Most Editor's Picks</th></tr>
<tr ><th >Artist</th><th >Picks</th></tr>
<?$carr105 = cc_stats_most_of_type('editorial_pick',7);$cc105= count( $carr105);$ck105= array_keys( $carr105);for( $ci105= 0; $ci105< $cc105; ++$ci105){    $_TV['a'] = $carr105[ $ck105[ $ci105 ] ];   ?><tr ><td ><a  href="<?= $_TV['a']['artist_page_url']?>" class="cc_user_link"><?= $_TV['a']['user_real_name']?></a></td><td ><?= $_TV['a']['c']?></td></tr>
<?}?></table>
<table  class="statstable">
<tr ><th  class="shead" colspan="3">Most Remixed A Cappellas</th></tr>
<tr ><th >Name</th><th >Aritst</th><th >Remixed</th></tr>
<?$carr106 = cc_stats_remixes_of_type('acappella');$cc106= count( $carr106);$ck106= array_keys( $carr106);for( $ci106= 0; $ci106< $cc106; ++$ci106){    $_TV['pellrmx'] = $carr106[ $ck106[ $ci106 ] ];   ?><tr ><td ><a  class="cc_file_link" href="<?= $_TV['pellrmx']['file_page_url']?>"><?= CC_strchop('$pellrmx/upload_name',30)?></a></td>
<td ><a  class="cc_user_link" href="<?= $_TV['pellrmx']['artist_page_url']?>"><?= $_TV['pellrmx']['user_real_name']?></a></td>
<td ><?= $_TV['pellrmx']['num_remixed']?></td></tr>
<?}?></table>
</div>