<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="en" xml:lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset={S_CONTENT_ENCODING}">
<meta http-equiv="Content-Style-Type" content="text/css">
{META}
{NAV_LINKS}

<title>{SITENAME} :: {PAGE_TITLE}</title>

<!-- BEGIN switch_enable_pm_popup -->
<script language="Javascript" type="text/javascript">
<!--
	if ( {PRIVATE_MESSAGE_NEW_FLAG} )
	{
		window.open('{U_PRIVATEMSGS_POPUP}', '_phpbbprivmsg', 'HEIGHT=225,resizable=yes,WIDTH=400');;
	}
//-->
</script>
<!-- END switch_enable_pm_popup -->


   <style>
     td.row1 { border-right: 1px #CCC solid; }
     td.row2 { border-right: 1px #CCC solid; }
     .spacerow { border-bottom: 1px #CCC solid; height: 17px;  }
   </style>

<script>
function cc_swap_link(str)
{
    var newlink = str.replace(/(.*)<a[^>]+>(.*)<\/a>/,'$1<a href="{CC_BASE_URL}/people/$2">$2</a>');
    document.write(newlink);
}
function cc_swap_flink(str)
{
    var newlink = str.replace(/(.*href=\")profile.php[^>]+>([^<]*)(<\/a>.*)/,'$1{CC_BASE_URL}/people/$2">$2$3');
    document.write(newlink);
}
function cc_swap_olink(str)
{
    //alert(str); return;
    var newlink = str.replace(/(<[^<]*>)?([^<]*).*/,'<a href="{CC_BASE_URL}/people/$2" class="gen">$2</a>');
    document.write(newlink);
}

</script>
<script src="{CC_BASE_URL}/forum/getheader{CC_QSTRING}"></script>
<a name="top"></a>
<table width="100%" cellspacing="0" cellpadding="10" border="0" align="center"> 
<!-- BEGIN CC_DONT_PRINT -->
	<tr> 
		<td class="bodyline">
		<table width="100%" cellspacing="0" cellpadding="0" border="0">
			<tr> 
             <td align="center" valign="top" nowrap="nowrap"><span class="mainmenu">&nbsp;<a href="{U_FAQ}" class="mainmenu"><img src="templates/subSilver/images/icon_mini_faq.gif" width="12" height="13" border="0" alt="{L_FAQ}" hspace="3" />{L_FAQ}</a></span><span class="mainmenu">&nbsp; &nbsp;<a href="{U_SEARCH}" class="mainmenu"><img src="templates/subSilver/images/icon_mini_search.gif" width="12" height="13" border="0" alt="{L_SEARCH}" hspace="3" />{L_SEARCH}</a>&nbsp; &nbsp;<a href="{U_MEMBERLIST}" class="mainmenu"><img src="templates/subSilver/images/icon_mini_members.gif" width="12" height="13" border="0" alt="{L_MEMBERLIST}" hspace="3" />{L_MEMBERLIST}</a>&nbsp; &nbsp;<a href="{U_GROUP_CP}" class="mainmenu"><img src="templates/subSilver/images/icon_mini_groups.gif" width="12" height="13" border="0" alt="{L_USERGROUPS}" hspace="3" />{L_USERGROUPS}</a>&nbsp; 
    <!-- BEGIN switch_user_logged_out -->
    &nbsp;<a href="{U_REGISTER}" class="mainmenu"><img src="templates/subSilver/images/icon_mini_register.gif" width="12" height="13" border="0" alt="{L_REGISTER}" hspace="3" />{L_REGISTER}</a></span>&nbsp;
    <!-- END switch_user_logged_out -->
			</td>
            
            </tr>
<tr><td height="25" align="center" valign="top" nowrap="nowrap"><span class="mainmenu">&nbsp;<a href="{U_PROFILE}" class="mainmenu"><img src="templates/subSilver/images/icon_mini_profile.gif" width="12" height="13" border="0" alt="{L_PROFILE}" hspace="3" />{L_PROFILE}</a>&nbsp; &nbsp;<a href="{U_PRIVATEMSGS}" class="mainmenu"><img src="templates/subSilver/images/icon_mini_message.gif" width="12" height="13" border="0" alt="{PRIVATE_MESSAGE_INFO}" hspace="3" />{PRIVATE_MESSAGE_INFO}</a>&nbsp; &nbsp;<a href="{U_LOGIN_LOGOUT}" class="mainmenu"><img src="templates/subSilver/images/icon_mini_login.gif" width="12" height="13" border="0" alt="{L_LOGIN_LOGOUT}" hspace="3" />{L_LOGIN_LOGOUT}</a>&nbsp;</span></td>
					</tr>
		</table>
	</tr>
<!-- END CC_DONT_PRINT -->
