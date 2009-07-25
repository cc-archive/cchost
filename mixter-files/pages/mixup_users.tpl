<!--
%%
[meta]
    name = mixup_users
    type = template_component
    desc = _('Display users for a mixups')
    dataview = mixup_users
    datasource = mixup_users
    embedded = 1
[/meta]

[dataview]
function mixup_users_dataview()
{
    $avatar = cc_get_user_avatar_sql('mixer');
    $other_avatar = cc_get_user_avatar_sql('other','other_avatar_url');
    $name = cc_fancy_user_sql('fancy_user_name','mixer');
    $other = cc_fancy_user_sql('mixup_user_other_full_name','other');
    $urlp = ccl('people') . '/';
    $urlf = ccl('files') . '/';
    $sql =<<<EOF
     SELECT {$avatar}, {$name}, {$other},
       CONCAT( '{$urlp}', mixer.user_name ) as artist_page_url,
       mixup_user_mixup as mixup_user_id,
       other.user_name as mixup_user_other_name,
       IF( mixup_user_upload, CONCAT('{$urlf}', mixer.user_name, '/', mixup_user_upload), '' ) as file_page_url
      FROM cc_tbl_mixup_user
                 JOIN cc_tbl_user as mixer ON mixup_user_user = mixer.user_id
      LEFT OUTER JOIN cc_tbl_user as other ON mixup_user_other = other.user_id
      %joins%
      %where%
      %order%
      
EOF;
   
   return array( 'e' => array(), 'sql' => $sql );
}
[/dataview]
%%
-->
<!-- template mixup_users -->

<?
    $NC = 4;
    $W = 100 / $NC;
    $rows = array_chunk($A['records'], $NC);
    
?>
<table>
%loop(#rows,cols)%
<tr>
  %loop(#cols,R)%
  <td  style="vertical-align:bottom;width:110px;text-align:center;padding:3px;">
     <div class="box miximgbox" style="height:120px" >
      <a class="hidemixup" href="%(#R/artist_page_url)%"><img src="%(#R/user_avatar_url)%" /></a>
      <br class="hidemixup" />
      <a href="%(#R/artist_page_url)%">%(#R/fancy_user_name)%</a>
      </div>
  </td>
  %end_loop%
</tr>
%end_loop%
</table>
