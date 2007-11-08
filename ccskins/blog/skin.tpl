
%append( style_sheets, css/blog.css)%

%map(form_fields, 'form_fields.tpl/stacked_form_fields')%

%if(ajax)%
    %call('short_page.tpl')%
%else%
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 //EN">
<html>
    %call('head.tpl')%
    %if(show_body_header)%
        %if_not_null(#_GET/popup)%
            %call('popup.tpl')%
        %else%
            %call('body.tpl')%
        %end_if%
    %else%
        %call('short_page.tpl')%
    %end_if%
</html>
%end_if%
