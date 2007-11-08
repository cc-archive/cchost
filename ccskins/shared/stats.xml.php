<?if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');
?><div >

<style >
table.statstable {
  border-width: 3px;
  border-style: solid;
  margin: 5px;
  float:left;
}

.shead {
  padding: 2px 1px 2px 1px;
  }
</style>
<?$A['total_uploads'] = cc_stats_total_uploads();$A['total_remixes'] = cc_stats_total_uploads('remix');$A['total_pells'] = cc_stats_total_uploads('acappella');$A['total_orgs'] = cc_stats_total_uploads('original');$A['total_samps'] = cc_stats_total_uploads('sample');$A['rtotal_pells'] = cc_stats_percent_remixed('acappella');$A['rtotal_orgs'] = cc_stats_percent_remixed('original');$A['rtotal_samps'] = cc_stats_percent_remixed('sample');?><table  class="statstable light_bg dark_border" style="float:left">
<tr ><th  class="shead dark_bg light_color" colspan="3">Overall Stats</th></tr>
<tr ><th ></th><th ></th><th >Remixed</th></tr>
<tr ><td >Total uploads:</td><td ><?= $A['total_uploads']?></td><td ></td></tr>
<tr ><td >Remixes:</td><td ><?= $A['total_remixes']?></td><td ></td></tr>
<tr ><td >A Cappellas:</td><td ><?= $A['total_pells']?></td><td ><?= $A['rtotal_pells']['percent']?>%</td></tr>
<tr ><td >Samples:</td><td ><?= $A['total_samps']?></td><td ><?= $A['rtotal_samps']['percent']?>%</td></tr>
<tr ><td >Fully Mixed:</td><td ><?= $A['total_orgs']?></td><td ><?= $A['rtotal_orgs']['percent']?>%</td></tr>
</table>
<table  class="statstable light_bg dark_border">
<tr ><th  class="shead dark_bg light_color" colspan="3">Most Sampled Artists</th></tr>
<tr ><th >Aritst</th><th >Sampled</th></tr>
<?$carr101 = cc_stats_most_remixed();$cc101= count( $carr101);$ck101= array_keys( $carr101);for( $ci101= 0; $ci101< $cc101; ++$ci101){    $A['smpld'] = $carr101[ $ck101[ $ci101 ] ];   ?><tr ><td ><a  class="cc_user_link" href="<?= $A['smpld']['artist_page_url']?>"><?= $A['smpld']['user_real_name']?></a></td>
<td ><?= $A['smpld']['num_remixed']?></td></tr>
<?}?></table>
<table  class="statstable light_bg dark_border">
<tr ><th  class="shead dark_bg light_color" colspan="2">Uploads by Month</th></tr>
<tr ><th >Month</th><th >Uploads</th></tr>
<?$carr102 = cc_stats_uploads_by_month();$cc102= count( $carr102);$ck102= array_keys( $carr102);for( $ci102= 0; $ci102< $cc102; ++$ci102){    $A['mostat'] = $carr102[ $ck102[ $ci102 ] ];   ?><tr ><td ><?= $A['mostat']['month']?>:</td><td ><?= $A['mostat']['c']?></td></tr>
<?}?></table>
<table  class="statstable light_bg dark_border">
<tr ><th  class="shead dark_bg light_color" colspan="2">Signups by Month</th></tr>
<tr ><th >Month</th><th >Signups</th></tr>
<?$carr103 = cc_stats_signups_by_month('2004-09');$cc103= count( $carr103);$ck103= array_keys( $carr103);for( $ci103= 0; $ci103< $cc103; ++$ci103){    $A['mostat'] = $carr103[ $ck103[ $ci103 ] ];   ?><tr ><td ><?= $A['mostat']['month']?>:</td><td ><?= $A['mostat']['c']?></td></tr>
<?}?></table>
<table  class="statstable light_bg dark_border">
<tr ><th  class="shead dark_bg light_color" colspan="2">Most Remixes</th></tr>
<tr ><th >Artist</th><th >Remixes</th></tr>
<?$carr104 = cc_stats_most_of_type('remix');$cc104= count( $carr104);$ck104= array_keys( $carr104);for( $ci104= 0; $ci104< $cc104; ++$ci104){    $A['a'] = $carr104[ $ck104[ $ci104 ] ];   ?><tr ><td ><a  href="<?= $A['a']['artist_page_url']?>" class="cc_user_link"><?= $A['a']['user_real_name']?></a></td><td ><?= $A['a']['c']?></td></tr>
<?}?></table>
<table  class="statstable light_bg dark_border">
<tr ><th  class="shead dark_bg light_color" colspan="2">Most Editor's Picks</th></tr>
<tr ><th >Artist</th><th >Picks</th></tr>
<?$carr105 = cc_stats_most_of_type('editorial_pick',7);$cc105= count( $carr105);$ck105= array_keys( $carr105);for( $ci105= 0; $ci105< $cc105; ++$ci105){    $A['a'] = $carr105[ $ck105[ $ci105 ] ];   ?><tr ><td ><a  href="<?= $A['a']['artist_page_url']?>" class="cc_user_link"><?= $A['a']['user_real_name']?></a></td><td ><?= $A['a']['c']?></td></tr>
<?}?></table>
<table  class="statstable light_bg dark_border">
<tr ><th  class="shead dark_bg light_color" colspan="3">Most Remixed A Cappellas</th></tr>
<tr ><th >Name</th><th >Aritst</th><th >Remixed</th></tr>
<?$carr106 = cc_stats_remixes_of_type('acappella');$cc106= count( $carr106);$ck106= array_keys( $carr106);for( $ci106= 0; $ci106< $cc106; ++$ci106){    $A['pellrmx'] = $carr106[ $ck106[ $ci106 ] ];   ?><tr ><td ><a  class="cc_file_link" href="<?= $A['pellrmx']['file_page_url']?>"><?= CC_strchop('$pellrmx/upload_name',30)?></a></td>
<td ><a  class="cc_user_link" href="<?= $A['pellrmx']['artist_page_url']?>"><?= $A['pellrmx']['user_real_name']?></a></td>
<td ><?= $A['pellrmx']['num_remixed']?></td></tr>
<?}?></table>
</div>