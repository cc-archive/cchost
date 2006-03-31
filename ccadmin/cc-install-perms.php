<?
/*
* Creative Commons has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file LICENSE.TXT.
* 
* You may use the ccHost software in accordance with the
* terms of that license. You agree that you are solely 
* responsible for your use of the ccHost software and you
* represent and warrant to Creative Commons that your use
* of the ccHost software will comply with the CC-GNU-GPL.
*
* $Header$
*
*/
if( !defined('HEAD_INCLUDED') )
    include( dirname(__FILE__) . '/cc-install-head.php');
?>

<h3>Information regarding UNIX file permission and ccHost</h3>

<p>Setting file permissions in the most efficient and secure way 
can be tricky with a content management system such as ccHost on a 
UNIX system.</p>

<p>There are many (i.e. many, many) UNIX configurations for 
running Apache and PHP and this document does not pretend to 
cover all. It is a compendium of issues encountered by various
ccHost installations under some popular scenarios. If there
are security issues on your server with ccHost or you would like to
avoid them, hopefully this document will arm you with
enough information to ask your system administrator the right
questions.</p>

<p>If you 'own' the server and have super-user (root) access then these 
problems are moot because you have the rights to do 
whatever you want with the directories and files created by
ccHost.</p>

<p>On the other hand if you are using a company server or a 
web hosting service, especially
on a 'shared server', then chances are you do not have such
access rights and that's where the issues begin.</p>

<p>In either case the thing to understand is that on a UNIX
system: <i>Whoever creates a file or directory on UNIX typically has full
(or enough) access rights to that file or directory to do what
ever they like.</i></p>

<h4>You are not nobody</h4>

<p>When you sign on to your web server via a terminal application (i.e.
shell) or transfer files via FTP you are operating as 
whoever you logged in as. When you copy the ccHost installation
onto your server (or unzip them directly there) the directories 
you create and files you copy into them are under your jurisdiction 
and UNIX considers you the 'user' (you 'own' them).</p>

<p>When ccHost is running on a UNIX server it is considered 
a PHP browser application running in an instance of Apache. Your
server administrator has set up all PHP applications like ccHost
to run, not as you, but as a special user, usually called 'nobody' 
and UNIX considers 'nobody' to be the <i>user</i> who owns the directory
and files.</p>

<p>The main point is: <i>These are two very distinctly different identities and the directories
and files created by ccHost are owned by this 'nobody' account, not you.</i></p>

<p>Evidence can be seen when you list out the directories in your ccHost
installation:</p>
<pre style="font-size:larger">
drwxrwxr-x   5 <b>victor</b> wgroup  4096 Jul 15 01:41 cctools  <span style="color:#66F">&lt;-- created by me during install</span>
drwxrwxr-x   5 <b>nobody</b> wgroup  4096 Jul 17 02:09 contests <span style="color:#66F">&lt;-- created by ccHost when I said 'Create Contest'</span>
</pre>

<h4>What ccHost Requires (IMPORTANT)</h4>

<p>For all cases: In order to install properly, 
ccHost (or more precisely
the PHP account ccHost is running as) must have write permissions in 
the root directory of the ccHost installation, in a directory under it called 
<i>cclib/phptal/phptal_cache</i> and where ever you told the installation to
put log files to. Specifically and in UNIX parlance this means 
full permission: Read, Write and Execute.</p>

<p>NOTE: The <i>people</i> directory is normally created by ccHost the first time something
is uploaded and the <i>contests</i> directory is normally created the first time something is 
uploaded into a contest. If these directories already exist then ccHost will only need permissions
in them, <i>cclib/phptal/phptal_cache</i> and your logfile directory -- not the root. Instructions 
for how to set these up (and whether you even have to) are covered below. </p>

<h4>Case 1: You and ccHost are the same user</h4>

<p>In the best case scenario, some web hosters make all of this go away by 
simply having PHP applications run under the <b>same account</b> as your login account. 
There is no 'nobody' account to contend with and ccHost is, in fact, running as you
so permission to create directories in the main directory or write files to 
the phptal_cache directory is given. So if you ask your system administrator
'Do PHP apps run under my user's account?' and if the answer is 'Yes' then you 
can skip down the <a href="#policy">ccHost policy section</a> because none of 
this applies to your ccHost installation.</p>

<h4>Case 2: You and ccHost are in the same group</h4>

<p>The next question you have to ask is whether your account and PHP account
is in the same <i>group</i>. You can see in the listing first mentioned 
above that this is case:</p>
<pre style="font-size:larger">
drwxrwxr-x   5 victor <b>wgroup</b>  4096 ....
drwxrwxr-x   5 nobody <b>wgroup</b>  4096 ....
</pre>
<p>This is important because while you may not be the same <i>user</i> as
the PHP account, UNIX has another level of security called <i>group</i> before
getting to the <i>global</i> space (the rest of the world).</p>
<p>If this is the case then you should be good to go, because the default
behavior of ccHost is to create directories and files with write access to 
anyone in the same group as ccHost. </p>

<h4>Case 3: You and ccHost are not in the same group</h4>

<p>If your login account and your PHP account are not even in the 
same group then you may still have an out:</p>

<p>Some web hosting companies provide you with a secure browser based
interface that allows you to bridge difference in user rights by giving you
behind-the-scenes circumvention to ownership issues in your space
on the server. When the ccHost
documentation prescribes things like 'setting write permissions on a 
directory' you should use this secure web interface provided by your
web hoster to perform the action. When you need to make changes to the 
files, use this interface. That way it won't matter if the
'nobody' account or your account created the directory or file, 
you can make the needed change.</p>

<p>If your login account and your PHP account are not in the 
same group and you haven't a secure web interface, then your system 
administrator or web hosting service
has left you with no choice to make these three directories 
available for writing to <i>global</i> users (a.k.a. <i>other</i>).
The parameter to <i>chmod</i>
is <i>o</i> for <i>other</i> (the letter o, not the number zero) :</p>
<pre style="font-size:larger">
mkdir contests
chmod o+rwx contests
mkdir people
chmod o+rwx people
chmod o+rwx cclib/phptal/phptal_cache
</pre>
<p>At this point any user logged into your server with any user account
in any user group can write, delete, create directories, etc. in the
<i>people</i> and <i>contests</i> directories where your and your
user's files live.</p>

<p>One possible work-around is to have a browser initiated PHP 
script temporarily set global access rights to write (using the PHP command chmod()) to
the ccHost tree. Now your 
user account will have write access via a shell or FTP to change files in the system. After
your changes to the ccHost directories and files are complete you can run another browser initiated 
PHP script to disable global access again.</p>

<p>The ccTools teams does not provide these kinds of scripts at this time.</p>

<a name="policy"></a>
<h3>ccHost Permissions Policy</h3>
<p>In order to walk the line between all the possible permutations, when ccHost creates 
a directory it assigns read, write and execute permissions to the <i>user</i> and <i>group</i>, and 
read and execute rights to <i>other</i> (0775). (Execute permission is required in order to access the directory at all.) </p>
<p>When ccHost accepts an upload and creates a file it assigns read, write permission to <i>user</i> and <i>group</i>
and read (only) to <i>other</i> (0664). (Execute permission is not required for a media file to be returned from a browser.)</p>
</body>
</html>
