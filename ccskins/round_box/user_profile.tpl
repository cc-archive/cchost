
<b>round boxes!</b>
%call('ccskins/shared/user_profile.tpl')%

<script>
var desc = $('user_description');
if( desc )
{
    Element.removeClassName(desc,'ufc');
    cc_round_box_mono(desc);
}
</script>


