<?
/*

Creative Commons has made the contents of this file
available under a CC-GNU-GPL license:

 http://creativecommons.org/licenses/GPL/2.0/

 A copy of the full license can be found as part of this
 distribution in the file LICENSE.TXT

You may use the ccHost software in accordance with the
terms of that license. You agree that you are solely 
responsible for your use of the ccHost software and you
represent and warrant to Creative Commons that your use
of the ccHost software will comply with the CC-GNU-GPL.

$Id$

*/
?>
<h1>ccHost Home page</h1>

<style type="text/css">
.hs
{
    font-style:Courier New, courier, serif;
    font-size: 12px;
    vertical-align: top;
}
</style>
<div class="hs">
    <? if( !empty($A['install_done']) ) { ?>

          <h2>ccHost <?= $A['cc-host-version'] ?> Home Page</h2>

          <? if( !empty($A['is_admin']) ) { ?>
              <p>You are logged in as an administrator</p>
          <? } ?>

            <?
                if( $zipfiles = glob('5*.zip') )
                {
                    list( $zipfile )= $zipfiles;
                    $filesize = filesize($zipfile);
                }
            ?>
          <div  style="width:550px;margin: 14px auto;">
          <div class="box">
            This is a pre-early-adoptors-curious-developers build of ccHost 5. Only install this on a web you don't care
            about because it will <b>DESTROY</b> any previous install of ccHost. 
            
            <? if( !empty($zipfile) ) { ?>
            You can pick up this build here:<h3><a style="display:block;font-size:16px;margin:9px;color:green;text-decoration:underline" href="<?= $zipfile ?>"><?= $zipfile ?></a> filesize: (<?= number_format($filesize/1000000,2) ?>MB) </h3>
            <? } ?>

            New features in cchost 5 (so far):

            <table style="float:right; margin: 5px;">
                <tr><td><img src="ccskins/shared/layouts/images/layout005.gif" /></td></tr>
                <tr><td><img src="ccskins/shared/layouts/images/layout023.gif" /></td></tr>
                <tr><td><img src="ccskins/shared/layouts/images/layout036.gif" /></td></tr>
            </table>

            <ul>
                <li><b>New Skin Engine</b>
                    <p>
                        The new skin engine allows for easy customization for admins and web developers. Shipping 
                        in the box are <b>40 layouts</b>, 3 <b>string profiles</b> for generic media sites, music sites and 
                        image sites, configurable <b>tab layouts</b>, <b>form layouts</b>, etc.
                    </p>
                </li>
                <li><b>Performance</b>
                    <p>
                        The new skin engine renders pages at over 10 times faster than the previous template engine and uses
                        less than 1/10th of the memory per page request.
                    </p>
                    <p>
                        A new database wrapper makes it easier to create custom templates and increases the speed of
                        queries by 3 times.
                    </p>
                    <p>
                        A new scheme for forum message and reviews makes rendering comment threads nearly 5 times
                        faster than previous versions.
                    </p>
            </ul>

            <br style="clear:right" />

            What's <span style="color:red;font-weight:bold">broken</span> in this build:

            <ul>
                <li>Most of the site. This is not a usable build for anything but getting a feel for what's coming</li>
            </ul>

          </div></div>

          <? if( !empty($A['not_admin']) ) { ?>
              <p>(You need to be logged in as Admin to configure the site.)</p>
          <? } ?>

    <? } else { 

           if( !empty($A['not_admin']) ) { ?>
                <p>Please <a href="<?= $A['home-url'] ?>login">CLICK HERE to login as admin</a> and finish the installation.</p>
          <? } 
           if( !empty($A['is_admin']) ) { ?>
                <div id="almost_done">
                      <p>You're <i>almost</i> done!</p>
                      <p>Please <a href="<?= $A['root-url'] ?>?update=1">CLICK HERE</a> to finish your ccHost installation.</p>
                </div>
                <? if( !empty($_GET['update']) ) { ?>
                   <script type="text/javascript">
                      var msg = 'OK! You are done! <a href="<?= $A['root-url'] ?>">CLICK HERE</a> to start using ccHost';
                      $('almost_done').innerHTML = msg;
                   </script>
                <? } 
           } 
     } ?>
</div>
