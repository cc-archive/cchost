<?if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

global $_TV;

_template_compat_required();
?><div >

<style >
.hs
{
    font-style:Courier New, courier, serif;
    font-size: 12px;
    vertical-align: top;
}
</style>
<div  class="hs">
<?if ( !empty($_TV['install_done'])) {?><h2 >Congratulations</h2>
<h2 >Welcome to ccHost <?= $_TV['cc-host-version']?></h2>
<p >Here are some places to start :</p>
<?if ( !empty($_TV['is_admin'])) {?><p >You are logged in as an administrator</p>
<p >Use the 'Manage Site' menu option to customize this installation.</p>
<?}?><ul >
<li >
              To change your password and other personal settings: 
              <a  class="cc_menu_link" href="<?= $_TV['home-url']?>people/profile/">Edit Profile</a>
</li>
</ul>
<?if ( !empty($_TV['not_admin'])) {?><p >(You need to be logged in as Admin to configure the site.)</p>
<?}}if ( !($_TV['install_done']) ) {if ( !empty($_TV['not_admin'])) {?><p >Please <a  href="<?= $_TV['home-url']?>login">login as admin</a> to finish the installation.</p>
<?}if ( !empty($_TV['is_admin'])) {?><p >You're <i >almost</i> done!</p>
<p >Please <a  href="<?= $_TV['home-url']?>?update=1">CLICK HERE</a> to finish your ccHost installation.</p>
<?}}?></div>
</div>