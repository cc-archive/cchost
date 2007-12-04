<table>
<tr>
<th>%text(str_forum_topic)%</th>
<th>%text(str_forum_author)%</th>
<th>%text(str_forum_replies)%</th>
<th>%text(str_forum_latest)%</th>
<th>%text(str_forum_post)%</th>
</tr>
%loop(threads,thread)%
 <tr>
   <td>
        <div class="thread_sticky_%(#thread/forum_thread_sticky)%"> </div>
        <a href="%(#thread/thread_url)%">%(#thread/oldest_topic_name)%</a>
   </td>
   <td>
        <a href="%(#thread/author_url)%">%(#thread/author_real_name)%</a>
   </td>
   <td>
        %(#thread/num_topics)%
   </td>
   <td>
      <a href="%(#thread/newest_topic_url)%">%(#thread/newest_topic_date)%</a>
    <div>%text(str_by)% %(#thread/newest_real_name)%
   </td>
</tr>
%end_loop%
</table>
%call(prev_next_links)%
