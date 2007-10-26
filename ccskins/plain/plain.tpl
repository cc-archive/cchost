
%macro(disable_tabs)%
<script>
    $$('.selected_tab a').each( function(e) { e.style.cursor = 'default'; e.href = 'javascript:// disabled'; } );
</script>
%end_macro%
