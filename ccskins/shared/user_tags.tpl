
%call('tag_filter')%
<script>
new ccTagFilter( { url: home_url + 'browse' + q + 'user=%(user_tags_user)%', 
                   target_url: home_url + 'people/%(user_tags_user)%/',
                   tags: '%(user_tags_tag)%' } );

</script>
