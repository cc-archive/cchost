<? $rows = array_chunk($A['collab_rows'], 2); ?>

<link  rel="stylesheet" type="text/css" href="%url( 'css/playlist.css' )%" title="Default Style"></link>
<link  rel="stylesheet" type="text/css" href="%url( 'css/collab.css' )%" title="Default Style"></link>
<table  style="width:95%">
%loop(#rows,cols)%
<tr>
  %loop(#cols,R)%
  <td  style="vertical-align: top;width:50%;">
    <div  class="collab_entry">
    <span  class="collab_date"><?= CC_datefmt($R['collab_date'],'M d, Y');?></span>
    <a  class="collab_name" href="%(home-url)%collab/%(#R/collab_id)%">%chop(#R/collab_name,35)%</a>
    <br  />
    %loop(#R/users,u)%
        <a  href="%(#u/artist_page_url)%">%(#u/user_real_name)%</a>%if_not_last(#u)%, %end_if%
    %end_loop%
    <br  style="clear:both" />
    </div>
  </td>
  %end_loop%
</tr>
%end_loop%
</table>
%call('prev_next_links')%
