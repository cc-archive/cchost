
%loop(forums,group)%
    <div class="forum_group">
        <div class="forum_group_name">%(#group/forum_group_name)%</div>
        %loop(#group/forums,forum)%
            <div class="forum">
                <div class="forum_name" >
                    <a href="%(home-url)%forums/%(#forum/forum_id)%">%(#forum/forum_name)%</a>
                </div>
                <div class="forum_description" >%(#forum/forum_description)%</div>
                <div>%text(str_forum_num_threads)% <!-- -->%(#forum/num_threads)%</div>
                <div>%text(str_forum_num_posts)% <!-- -->%(#forum/num_posts)%</div>
                <div>%text(str_forum_latest_post)% 
                  <a href="%(home-url)%thread/%(#forum/latest_post/forum_thread_id)%#%(#forum/latest_post/forum_thread_newest)%">%(#forum/latest_post/forum_thread_date)%</a>
                </div>
                <div>%text(str_by)% %(#forum/latest_post/user_real_name)%
               </div>
            </div>
        %end_loop%
    </div>
%end_loop%
