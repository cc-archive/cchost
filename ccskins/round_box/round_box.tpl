
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
    <script>cc_round_boxes();</script>
%end_macro%