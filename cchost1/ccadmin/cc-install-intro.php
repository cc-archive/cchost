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
?>
<h2>Welcome to ccHost Installation</h2>
<p>We'll ask you for some information, you're going to give it to us and hopefully, working
together we can get you up and running in just a few minutes. But first...</p>
<h3>Installation Requirements</h3>
<p>In order to run ccHost you need to install some other software and perform
one database task.</p>
<ul>
<li><b>PHP 4</b>
<p> <? print $vmsg; ?></p></li>

<li><b>mySQL</b>
<p>Currently mySQL is the only database supported by ccHost. No doubt this will change someday but for now
this is the only one. While there is no known reason why it shouldn't work on any moderately recent version
it should be noted the ccHost code has only been tested on a few version <b>4</b> installations.</p></li>

<li><b>Create a Database</b>
<p>Before you continue installing ccHost you need to create a database. (If you are running at a hosted site
the administrators can either do it for you or have already told you how to do it.) You will need the
name of the database and the name and password of a user with CREATE TABLE (amongst other) rights. You'll have a chance
to change users after you install the rest of the site. </p></li>

<li><b>GetID3</b>
<p>(Technically ccHost is 
supposed to work even if you don't have this library installed but it has not been extensively
tested so the safest thing for now is to just install it.)</p>

<p>ccHost uses the <a href="http://www.getid3.org/">GetID3 library</a> to verify 
the formats of file uploads of all types of media and archive files. It also uses it to tag
ID3 format files (like MP3s) with things like artist, song title, license, etc. This site has been tested
with version <b>1.7.2</b> and no others, therefore no other version of the library is supported. You can download this version from
<a href="http://www.getid3.org/#download">here</a>. </p>

<p>Installing GetID3 is actually very simple: just unzip the library to a directory (e.g. <b class="d"><? print $id3suggest; ?></b>).</p>

</li>

<li><b>phpBB2</b>
<p>Completely optional is the ability to interact with an installation of phpBB2 you
might have on the same server as ccHost. If you want to give ccHost users the ability to comment
on uploads, bascially creating threads for each one, ccHost knows how to tightly integrate into
your existing phpBB2 installation without any modifications at all that system. You should however,
create a new forum, probably called 'Reviews' or 'Comments on Media', and take note at the internal
forum number (visible in the URL when you browse to the forum). You will need to remember this
number when you are ready to install ccHost.</p>
<p>This integration has been tested on phpBB2 version <b>2.0.13</b> and no others. You guess the rest.</p>
</li></ul>

<h3>If you've done all the above onto <a href="?step=1a">the next step...</a></h3>
