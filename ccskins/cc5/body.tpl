<body>
%if_not_empty(site-disabled)%
    <div id="site_disabled_message" style="position:absolute">%text(str_site_disabled)%</div>
%end_if%
%if_not_empty(beta_message)%
    <div id="beta_message" style="position:absolute;">%(beta_message)%</div>
%end_if%

<div class="hide">
  <a href="#content">%text(skip)%</a>
</div>

 <div id="globalWrapper">
   <div id="headerWrapper">
     <div id="headerLogo">
       <h1><a href="%(root-url)%" title="%(site-title)%" >
        %if_not_null(logo/src)% 
            <? $bimg = ccd($A['logo']['src']); ?><img src="%(#bimg)%" style="width:%(logo/w)%px;height:%(logo/h)%px"/> 
        %else% 
            <span class="light_color">%(site-title)%</span>
        %end_if%
          </a></h1>
     </div>
     
     <div id="headerNav">
        %if_not_empty(tab_pos/in_header)%
            %call('tabs.tpl/print_tabs')%
        %end_if%
     </div>
   </div><!-- headerWrapper -->
   
   <div id="mainContent" class="ccbox">
     <!-- toolboxes -->
%call(print_bread_crumbs)%

%if_not_empty(tab_pos/subclient)%
     <div id="pageNav">
    %call('tabs.tpl/print_sub_tabs')% <!-- -->
     </div>
%end_if%
     

     <div class="block" id="page_title">
 	    <div class="sideitem">
         <form method="get" id="searchform" action="%(home-url)%search/results">
           <div>
             <input title="Search" accesskey="f"
                 value="" name="search_text" id="search_text" class="inactive" type="text">
                 <input id="searchsubmit" value="Go" type="submit">
                <input type="hidden" name="search_in" value="all"></input>
           </div>
         </form>
    %if_null(logged_in_as)%
         <span><a href="%(home-url)%login">Log in / create account</a></span>
         %if_not_empty(openid-type)%
          <span>(<a href="%(home-url)%login/openid">OpenID</a>)</span>
         %end_if%
    %else%
         <span>%text(loggedin)%: <b><span>%(logged_in_as)%</span></b> 
            <span><a class="small_button" href="%(home-url)%logout">%text(logout)%</a></span>
    %end_if%
         
       </div><!-- sideitem -->
       
%if_not_empty(page-title)%
    <h1 class="page_title">%text(page-title)%</h1>
%end_if%
    </div><!-- block #title -->
     
    <!-- page content -->
    <div id="contentPrimary">
	  <div class="block page">
            		
<a name="content" ></a>    
<div id="inner_content">
<?
    if( !empty($A['macro_names'] ) )
        while( $macro = array_shift($A['macro_names']) )
            $T->Call($macro);
?>
</div><!-- inner_content -->
%loop(inc_names,inc_name)%   %call(#inc_name)% %end_loop%

<!-- end content -->

</div><!-- block page -->
</div><!-- content primary -->
</div><!-- main content -->
   
   <!-- footer -->
   <div id="footer">
     <div id="footerContent" class="ccbox">%text(footer)%</div>
     <div id="footerLicense">
       <p class="ccbox">
         %text(site-license)%
       </p>
     </div>
   </div>
   
 </div><!-- global wrapper -->
%loop(end_script_links,script_link)%
    <script type="text/javascript" src="%url(#script_link)%" ></script>
%end_loop%

%loop(end_script_blocks,block)%
    %call(#block)%
%end_loop%

<script type="text/javascript"> 
    new modalHook( [ 'search_site_link', 'mi_login', 'mi_register']);  
    $$('.selected_tab a').each( function(e) { e.style.cursor = 'default'; e.href = 'javascript:// disabled'; } );
%loop(end_script_text,tblock)%
    %(#tblock)%
%end_loop%
   
</script>
<div id="menu_block" style="position:absolute">
    <a href="javascript://main menu" class="small_button" id="menu_expander">Show Menu &gt;&gt;&gt;</a>
    <div id="menu_hang" class="box" style="display:none">
        %loop(menu_groups,group)%
            <div class="menu_group">
                <p>%text(#group/group_name)%</p>
                <ul>%loop(#group/menu_items,mi)%
                    <li><a href="%(#mi/action)%" %if_attr(#mi/id,id)%>%text(#mi/menu_text)%</a></li>
                %end_loop% </ul>
            </div>
        %end_loop%
        %if_null(edit_extra)%
            %settings(extras,custom_macros)%
            %loop(custom_macros/macros,mac)%
                <div class="menu_group">        
                    %call_macro(#mac)%
                </div>
            %end_loop%
        %else%
            <!-- editing extras -->
            %(edit_extra)%
        %end_if%
        <br class="menu_hang_breaker" />
    </div>
</div><!-- menu block -->
<script>
var menu_expanded = 0;
function expand_main_menu()
{
    try {
    if( menu_expanded )
    {
        $('menu_expander').innerHTML = 'Show Menu &gt;&gt;&gt;';
        $('menu_hang').style.display = 'none';
        menu_expanded = 0;
    }
    else
    {
        $('menu_expander').innerHTML = 'Hide Menu &lt;&lt;&lt';
        $('menu_hang').style.display = 'block';
        menu_expanded = 1;
    }
    }
    catch(e)
    {
        alert(e);
    }
}
function hook_main_menu()
{
    Event.observe('menu_expander','click',expand_main_menu);
}
hook_main_menu();
</script>
</body>
