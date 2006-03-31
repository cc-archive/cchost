
<script>
function cc_get_quote_links(txt)
{
    //<!-- BEGIN switch_user_logged_in -->
    tquote  = cc_dump_quote_link(txt,"icon_quote.gif","Reply with quote");
    tedit   = cc_dump_quote_link(txt,"icon_edit.gif","Edit");
    tdelete = cc_dump_quote_link(txt,"icon_delete.gif","Delete");
    tip     = cc_dump_quote_link(txt,"icon_ip.gif","IP");

    txt2 = '<table><tr><td>' + tquote + '</td><td>' + tedit + '</td><td>' + tdelete + '</td><td>' +
          tip + '</td></tr></table>';

    document.write(txt2);
    //<!-- END switch_user_logged_in -->
}
//<!-- BEGIN switch_user_logged_in -->
function cc_dump_quote_link(txt,icon,caption)
{
  if( txt.indexOf(icon) > 0 )
  {
    var regex = new RegExp(".*<a href=\"([^\"]*)\"><img src=\"[^\"]+" + icon + "\"[^<]+<\/a>.*");
    var txt = txt.replace(regex,
            "<a class=\"cc_gen_button\" href=\"$1\"><span>" + caption + "</span></a>");
    return(txt);
  }
  return( '' );
}
//<!-- END switch_user_logged_in -->
</script>
<h1 id="page_title">{TOPIC_TITLE}</h1>
<!-- table width="100%" cellspacing="2" cellpadding="2" border="0">
  <tr> 
  <td align="left" valign="bottom" colspan="2"><a class="maintitle" href="{U_VIEW_TOPIC}">{TOPIC_TITLE}</a><br />
    <span class="gensmall"><b>{PAGINATION}</b><br />
    &nbsp; </span></td>
  </tr>
</table -->

<table width="100%" cellspacing="2" cellpadding="2" border="0">
  <tr> 
    <td align="left" valign="bottom" nowrap="nowrap">
      <span class="nav">
        <!-- BEGIN switch_user_logged_in -->
        <a class="cc_gen_button" href="{U_POST_REPLY_TOPIC}"><span>Reply</span></a>
        <!-- END switch_user_logged_in -->
      </span>
    </td>
    <td align="left" valign="middle" >
      <span class="nav">&nbsp;&nbsp;&nbsp;<a href="{U_INDEX}" class="nav">{L_INDEX}</a> 
      -> <a href="{U_VIEW_FORUM}" class="nav">{FORUM_NAME}</a></span>
    </td>
    <td class="catHead" align="right" >
      <span class="nav" style="white-space:nowrap">
        <a href="{U_VIEW_OLDER_TOPIC}" class="nav">{L_VIEW_PREVIOUS_TOPIC}</a> :: 
        <a href="{U_VIEW_NEWER_TOPIC}" class="nav">{L_VIEW_NEXT_TOPIC}</a>
      </span>
     </td>
  </tr>
</table>

<table class="forumline" width="100%" cellspacing="1" cellpadding="3" border="0">
    <!-- cchost showrecord() -->
    <tr><td colspan="2">
        <div id="cc_media_head" class="cc_media_head" style="width:80%;margin-left:10%;"></div>
        <script> cc_get_data("{CC_HOST_ROOT}?ccm=/{CC_CFG_ROOT}/forum/showrecord/{TOPIC_ID}","cc_media_head","getting data..."); </script>
    </td></tr>
    <!-- end cchost showrecord() -->
  <tr>
    <th class="thLeft" width="150" height="26" nowrap="nowrap">{L_AUTHOR}</th>
    <th class="thRight" nowrap="nowrap">{L_MESSAGE}</th>
  </tr>
    </tr>
  <!-- BEGIN postrow -->
  <tr> 
    <td colspan="2"><hr /></td>
  </tr>
  <tr> 
 <td width="150" align="left" valign="top" class="{postrow.ROW_CLASS}">
  <span class="name">
    <a name="{postrow.U_POST_ID}"></a>
    <a href="{CC_BASE_URL}/people/{postrow.POSTER_NAME}">{postrow.POSTER_NAME}</a>
  </span>
  <br />
  <span class="postdetails">
    {postrow.POSTER_RANK}<br />
    {postrow.RANK_IMAGE}{postrow.POSTER_AVATAR}<br />
    {postrow.POSTER_POSTS}<br />
  </span>
  </td>
  <td class="{postrow.ROW_CLASS}" width="100%" height="28" 
   valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
  <td width="100%">
  <!-- BEGIN CC_NO_SHOW -->
    <a href="{postrow.U_MINI_POST}"><img src="{postrow.MINI_POST_IMG}" 
    width="12" height="9" alt="{postrow.L_MINI_POST_ALT}" title="{postrow.L_MINI_POST_ALT}" border="0" /></a>
  <!-- END CC_NO_SHOW -->
  <span style="font-style:italic" class="postdetails">{L_POSTED}: {postrow.POST_DATE}
  <!-- BEGIN CC_NO_SHOW -->
    <span class="gen">&nbsp;</span>&nbsp; &nbsp;{L_POST_SUBJECT}: {postrow.POST_SUBJECT}
  <!-- END CC_NO_SHOW -->
  </span>
  </td>
  <td valign="top" nowrap="nowrap"><script>cc_get_quote_links('{postrow.QUOTE_IMG} {postrow.EDIT_IMG} {postrow.DELETE_IMG} {postrow.IP_IMG}');</script>
  </td>
      </tr>
      <tr> 
        <td colspan="2"><br /></td>
      </tr>
      <tr>
        <td colspan="2"><span class="postbody">{postrow.MESSAGE}{postrow.SIGNATURE}</span><span class="gensmall">{postrow.EDITED_MESSAGE}</span></td>
      </tr>
    </table></td>
  </tr>
  <tr> 
    <td class="{postrow.ROW_CLASS}" width="150" align="left" valign="middle"><span class="nav"><a href="#top" class="nav">{L_BACK_TO_TOP}</a></span></td>
    <td class="{postrow.ROW_CLASS}" width="100%" height="28" valign="bottom" nowrap="nowrap"><table cellspacing="0" cellpadding="0" border="0" height="18" width="18">
          <!-- BEGIN CC_NO_SHOW -->
      <tr> 
        <td valign="middle" nowrap="nowrap">{postrow.PROFILE_IMG} {postrow.PM_IMG} {postrow.EMAIL_IMG} {postrow.WWW_IMG} {postrow.AIM_IMG} {postrow.YIM_IMG} {postrow.MSN_IMG}<script language="JavaScript" type="text/javascript"><!-- 

  if ( navigator.userAgent.toLowerCase().indexOf('mozilla') != -1 && navigator.userAgent.indexOf('5.') == -1 && navigator.userAgent.indexOf('6.') == -1 )
    document.write(' {postrow.ICQ_IMG}');
  else
    document.write('</td><td>&nbsp;</td><td valign="top" nowrap="nowrap"><div style="position:relative"><div style="position:absolute">{postrow.ICQ_IMG}</div><div style="position:absolute;left:3px;top:-1px">{postrow.ICQ_STATUS_IMG}</div></div>');
        
        //--></script><noscript>{postrow.ICQ_IMG}</noscript></td>
      </tr>
          <!-- END CC_NO_SHOW -->
    </table></td>
  </tr>
  <tr> 
    <td class="spaceRow" colspan="2" height="1"><img src="templates/cchost/images/spacer.gif" alt="" width="1" height="1" /></td>
  </tr>
  <!-- END postrow -->
  <tr align="center"> 
    <td class="catBottom" colspan="2" height="28"><table cellspacing="0" cellpadding="0" border="0">
      <tr><form method="post" action="{S_POST_DAYS_ACTION}">
        <td align="center"><span class="gensmall">{L_DISPLAY_POSTS}: {S_SELECT_POST_DAYS}&nbsp;{S_SELECT_POST_ORDER}&nbsp;<input type="submit" value="{L_GO}" class="liteoption" name="submit" /></span></td>
      </form></tr>
    </table></td>
  </tr>
</table>
<!-- BEGIN switch_user_logged_in -->
<table><tr><td>
<a class="cc_gen_button" href="{U_POST_REPLY_TOPIC}"><span>Reply</span></a></td></tr></table>
<!-- END switch_user_logged_in -->

<table width="100%" cellspacing="2" cellpadding="2" border="0" align="center">
  <tr> 
  <td align="left" valign="middle" nowrap="nowrap"><span class="nav">        <!-- BEGIN switch_user_logged_in -->
<a href="{U_POST_NEW_TOPIC}"><img src="{POST_IMG}" border="0" alt="{L_POST_NEW_TOPIC}" align="middle" /></a>&nbsp;&nbsp;&nbsp;<a href="{U_POST_REPLY_TOPIC}"><img src="{REPLY_IMG}" border="0" alt="{L_POST_REPLY_TOPIC}" align="middle" /></a>        <!-- END switch_user_logged_in -->
</span></td>
  <td align="left" valign="middle" width="100%"><span class="nav">&nbsp;&nbsp;&nbsp;<a href="{U_INDEX}" class="nav">{L_INDEX}</a> 
    -> <a href="{U_VIEW_FORUM}" class="nav">{FORUM_NAME}</a></span></td>
  <td align="right" valign="top" nowrap="nowrap"><span class="gensmall">{S_TIMEZONE}</span><br /><span class="nav">{PAGINATION}</span> 
    </td>
  </tr>
  <tr>
  <td align="left" colspan="3"><span class="nav">{PAGE_NUMBER}</span></td>
  </tr>
</table>

<table width="100%" cellspacing="2" border="0" align="center">
  <tr> 
  <td width="40%" valign="top" nowrap="nowrap" align="left"><span class="gensmall">{S_WATCH_TOPIC}</span><br />
    &nbsp;<br />
    {S_TOPIC_ADMIN}</td>
  <td align="right" valign="top" nowrap="nowrap">{JUMPBOX}<span class="gensmall"> <!-- BEGIN CC_DONT_SHOW -->
{S_AUTH_LIST} <!-- END CC_DONT_SHOW -->
</span></td>
  </tr>
</table>
