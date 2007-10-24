

%import_map(ccskins/default)%
%append(style_sheets,css/skin-default.css)%
%append(end_script_blocks,skin.php/post_script)%

%macro(init)%
  %call(html_head)%
  %call(main_body)%
%end_macro%

%inherit(user_listing,skin.tpl/user_profile)%

%macro(user_profile)%
    %call_parent%
    <script>
    var desc = $('user_description');
    if( desc )
    {
        Element.removeClassName(desc,'ufc');
        cc_round_box_bw(desc);
    }
    </script>
%end_macro%

%macro(post_script)%
    <script>
    new modalHook( [ 'mi_login', 'mi_register', 'search_site_link' ]); 
    </script>
%end_macro%