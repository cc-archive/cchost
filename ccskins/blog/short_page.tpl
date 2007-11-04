
%if(page_title)%
    <h1 class="title">%text(page-title)%</h1>
%end_if%
%loop(macro_names,macro)%    
    %call(#macro)%             
%end_loop%
%loop(inc_names,inc_name)%   
    %call(#inc_name)%
%end_loop%
