<?if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

function _t_home_init($T,&$targs) {
    $T->CompatRequired();
}?><div >

<style >
.hs
{
    font-style:Courier New, courier, serif;
    font-size: 12px;
    vertical-align: top;
}
</style>
<div  class="hs">
<?if ( !empty($A['install_done'])) {?><h2 >Congratulations</h2>
<h2 >ccHost <?= $A['cc-host-version']?> Home Page</h2>
<p >Here are some places to start :</p>
<?if ( !empty($A['is_admin'])) {?><p >You are logged in as an adiminstrator</p>
<p >Use the 'Manage Site' menu option to customize this installation.</p>
<?}?><ul >
<li >
              To change your password and other personal settings: 
              <a  class="cc_menu_link" href="<?= $A['home-url']?>people/profile">Edit Profile</a>
</li>
</ul>
<?if ( !empty($A['not_admin'])) {?><p >(You need to be logged in as Admin to configure the site.)</p>
<?}}if ( !($A['install_done']) ) {if ( !empty($A['not_admin'])) {?><p >Please <a  href="<?= $A['home-url']?>login">login as admin</a> to finish the installation.</p>
<?}if ( !empty($A['is_admin'])) {?><p >You're <i >almost</i> done!</p>
<p >Please <a  href="<?= $A['home-url']?>?update=1">CLICK HERE</a> to finish your ccHost installation.</p>
<?}}?></div>
</div>