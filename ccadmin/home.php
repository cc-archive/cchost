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

<style>
.hs
{
    font-style:Courier New, courier, serif;
    font-size: 12px;
    vertical-align: top;
}
</style>
<div class="hs">
    <? if( !empty($A['install_done']) ) { ?>

          <h2>Congratulations</h2>
          <h2>ccHost <?= $A['cc-host-version'] ?> Home Page</h2>

          <p>Here are some places to start :</p>

          <? if( !empty($A['is_admin']) ) { ?>
              <p>You are logged in as an administrator</p>
              <p>Use the 'Manage Site' menu option to customize this installation.</p>
          <? } ?>

          <ul>
            <li>
                To change your password and other personal settings: 
                <a class="cc_menu_link" href="<?= $A['home-url'] ?>people/profile">Edit Profile</a>
            </li>
          </ul>

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
                   <script>
                      var msg = 'OK! You are done! <a href="<?= $A['root-url'] ?>">CLICK HERE</a> to start using ccHost';
                      $('almost_done').innerHTML = msg;
                   </script>
                <? } 
           } 
     } ?>
</div>
