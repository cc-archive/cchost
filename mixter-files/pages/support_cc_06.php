<?
if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

function _t_support_cc_06_init($T,&$targs) {
    
}
?><h1  class="cc_hide">Support ccMixter</h1>
<h1 ><img  src="http://creativecommons.org/images/support/2006/support-title.png" /></h1>
<div  style="font-size: 17px;font-family:arial;">
<p >A friendly notice from the folks that bring you ccMixter.org...</p>
<p >It's been an amazing year for ccMixter. For just the new few weeks Creative Commons is having their annual drive, 
a perfect opportunity for you to show
your appreciation. ccMixter is provided as a free remix hosting service in order to
demonstrate the artistic power and possibilities of open licensing.</p>
<p >We appreciate
<i >any</i> support you can show by using the "Support CC" button on the left.</p>
<h3  style="text-align:center">
<a  style="color:green" href="http://creativecommons.org/support/">
<img  src="http://creativecommons.org/images/support/2006/spread-3.gif" /><br  />
http://creativecommons.org/support/</a></h3>
<p >ccMixter is sponsored 100% by Creative Commons, which is sponsored by you. Thanks!</p>
</div>
<script type="text/javascript">
  //<!--
  var tab_top = -41;

  function timed_tab_grow()
  {
     ++tab_top;
     var obj = new cc_obj('cc_tab');
     obj.style.backgroundPosition    = "0px " + tab_top + "px";
     if( tab_top < 0 )
     {
        setTimeout("timed_tab_grow()", 50);
     }
  }

  tabobj = new cc_obj('cc_tab');
  tabobj.obj.innerHTML = '<a href="http://creativecommons.org/">&nbsp;</a>';
  timed_tab_grow();
  //-->
</script>