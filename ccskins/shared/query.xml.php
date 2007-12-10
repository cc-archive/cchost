<?if( !defined('IN_CC_HOST') )
    die('Welcome to ccHost');

?><style >

#ptable,
#ftable { 
  border-top: 2px solid #bbb; 
  margin-top: 23px;
}

#ptable th {
  background-color: #444;
  color: white;
  border: 2px solid white;
  padding: 3px;
  }

#ptable td,
#ftable td { 
  border-bottom: 1px solid #bbb; 
  padding: 4px; 
  vertical-align: top; 
  font-family: Verdana;
}

.cc_code {
  margin:20px 0px 20px 20px;
  font-family:Courier New;
}

.note {
  font-family:Comic Sans MS, arial;
  background-color:#ffff00;
}

h2 { 
 background-color: #CCE;
 padding: 2px;
}

h3 {
 background-color: #DDE;
 width: 50%;
 padding: 2px;
 }

.def {
  background-color: #DDD;
  border: 3px solid white;
}
</style>
<h1 > ccHost Query/Formatter Engine 1.0</h1>
<p >
The ccHost Query Engine is a powerful and extensible engine for retrieving and displaying upload records. The Query Engine
is the single feature that powers much of ccMixter, from the navigation tabs, Remix Radio, Publicize, Playlists, Picks Page,
Sample Browser, and dozens of other places.
</p>
<p >
 There are two sides to the same engine, the input parameters and output formats. The engine can be invoked from many places
 including the browser, code or templates.
</p>
<h2 > Input Parameters </h2>
<p >For all ways of engagine the engine (url, programmatic, template, etc.) the input is formatted as 
a browser query string (or form post variables)</p>
<div  class="cc_code">
  limit=5&tags=remix+chill&amp;sort=name&amp;f=html&amp;t=links
</div>
<h3 >Reference</h3>
<p >Some parameters have two versions: long and short. For example the following will yeild the same results:</p>
<div  class="cc_code">
/media/api/query?<b >f</b>=html&<b >t</b>=links&tags=chill<br  /><br  />
/media/api/query?<b >format</b>=html&<b >template</b>=links&tags=chill
</div>
<p >These are supported parameters where the short form, if applicable, follows long in the 
format <b >long_form</b>,<b >short_form</b>:</p>
<table  id="ptable" border="0" cellpadding="0" cellspacing="0">
<tbody >
<tr >
<th > param</th><th >description</th><th >default</th></tr>
<tr >
<td > tags
</td><td > Return records with these tags. Use the minus '-' to exclude uploads with given tags.
</td>
<td  class="def"> remix,-digital_distorion
</td>
</tr>
<tr >
<td > type
</td>
<td > Specifies whether the upload must have 'all' the tags or 'any' of the tags
</td>
<td  class="def"> all
</td>
</tr>
<tr >
<td > reqtags
</td>
<td > Return only records with these tags. These are added to whatever tags are specified in 'tags' and 'type'. Typical usage:
<div  class="cc_code">reqtags=audio
<br  />
 tags=chill+female_vocals
<br  />
 type=any
<br  />
</div>  
  In this case only records that match <b >audio</b> AND (<b >chill </b>or <b >female_vocals</b>) tags
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  sort
</td>
<td >  Return records sorted on one of these values:
  <ul >
<li >user - artist login name</li>
<li >fullname - artist full name</li>
<li >name - upload name </li>
<li >lic - license</li>
<li >date - upload date </li>
<li >last_edit - upload's last edit</li>
<li >remixes - total number of remixes</li>
<li >local_remixes - number of remixes at this site</li>
<li >pool_remixes - number of remixes at remote sites</li>
<li >sources - total number of sources </li>
<li >local_sources - number of sources at this site</li>
<li >pool_sources - number of remote sources</li>
<li >score - user rating for upload</li>
<li >num_scores - number of times rated</li>
<li >rank - site ranking for upload</li>
<li >registered - artist sign up date </li>
<li >user_remixes - number of remixes for artist</li>
<li >remixed - number of times artist has been remixed </li>
<li >uploads - number of uploads by artist</li>
<li >userrank - overall ranking of artist</li>
<li >userscore - overall rating for artist </li>
<li >user_num_scores - number of times user has been rated</li>
<li >user_reviews - number reviews left by artist </li>
<li >user_reviewed - number of reviews left for artist </li>
<li >posts - number of forum posts by artist </li>
<li >id - internal upload id </li>
</ul>
</td>
<td  class="def">  date
</td>
</tr>
<tr >
<td >  ord
</td>
<td >  Return records sorted in either ascending ('ASC') or descending ('DESC') order.
</td>
<td  class="def">  DESC
</td>
</tr>
<tr >
<td >  ids
</td>
<td >  Return records that match exactly the a semi-colon separated list of upload ids. See the format=ids in the next section for how these can be obtained. Note: you should take care in the browser because this list could get very long and it is easy to overflow a GET request.
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  nosort
</td>
<td > Return the records specified with 'ids' in exactly the order given. A value of '1' means
don't do any additional sort, '0' means do whatever is in the 'sort' and 'ord' parameters. If no
'sort' parameter is given the records are returned in an order determined by the system.
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  rand
</td>
<td >  Return records in distinctly random order. (Perfect for playlists). A value of '1' means do the random sort. This overrides sort, nosort and ord.
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  limit
</td>
<td >  Limits the number of records returned. The actual top limit for browser GET/POST requests is set by the admin of that ccHost site. (Hint: 200 is a lot). Programmatic requests can set the limit to '0' to get all matching records
</td>
<td  class="def">(determined<br  /> by admins)  
</td>
</tr>
<tr >
<td >  offset
</td>
<td >  Return records starting at offset. Perfect for paging through search results
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  sinceu
</td>
<td >  Return records since this date, UNIX time format (as returned by php's time())
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  sinced
</td>
<td >  Return records since this date, text format (e.g. '2005-01-30', 'two weeks ago', etc.)
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  lic
</td>
<td >  Return records only of this license. Values allowed:
<br  />
<ul >
<li >  by</li>
<li >  nc</li>
<li >  sa&nbsp; (for sharealike)</li>
<li >  nod (for noderivs)</li>
<li >  byncsa</li>
<li >  byncnd</li>
<li >  s (for sampling)</li>
<li >  splus</li>
<li >  ncsplus</li>
<li >  pd (for publicdomain)</li>
</ul>  
  Don't specifiy a 'lic' or leave it empty ('') to return all
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  score
</td>
<td >  Return records with a minium rating score. This 'score' is 100 times the star rating. For example, an upload rated 3.45 has a score of 345. Valid values are 0 or 100 to return all records or any value between 100 and 500.
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  user, u
</td>
<td >  Only return records from this user
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  remixesof
</td>
<td >  Only return records that are remixes of this user
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  remixedby
</td>
<td >  Only return records that are remixes by this user (same as user=someuser&amp;reqtags=remix)
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  reccby
</td>
<td >  Only return records that are rated 5 by this user (aka "Recommended by...")
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  promo_tag
</td>
<td >  Uploads with this tag will be mixed into the output stream at promo_gap intervals. (For ccMixter this tag is <b >site_promo</b>)
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  promo_gap
</td>
<td >  Updloads with whatever the promo_tag is are mixed into the output stream at this interval
</td>
<td  class="def">  4
</td>
</tr>
<tr >
<td >  query,q
</td>
<td >  Return records that contain this query search string. The string is matched against tags, user name, upload name and upload description. <span  style="background-color:#ffff66; FONT-STYLE:italic">(This is currently disabled for browser access)</span>
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  where
</td>
<td >  mySQL 'WHERE' expression appended to everything else. This will always be disabled for browser access.
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  mod
</td>
<td >  Return records that have been moderated (admins only). Valid values are 1 or 0
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  unpub
</td>
<td >  Return unpublished uploads (admins and upload owners only). Valid values are 1 or 0
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  format, f
</td>
<td >  Return records in this format. (see 'Output Formats' below)
</td>
<td  class="def">page
</td>
</tr>
<tr >
<td >  template, t
</td>
<td >  Return records formatted by this template. For format=html or docwrite only. The template must reside in the 'Skins' ccHost path or optionally in a directory called 'formats' below. <br  />
Currently supported are:
  <ul >
<li >links - links to the uploads' page</li>
<li >links_by - uploads page and artists' page links</li>
<li >links_dl - upload page and media download links</li>
<li >links_ul - links to the uploads's page in &lt;ul&gt; tags</li>
<li >links_by_ul - uploads page, artists' page in &lt;ul&gt; tags</li>
<li >links_by_dl_ul - upload page, user page, download links in &lt;ul&gt; tags</li>
<li >links_stream - upload page links with media stream links</li>
<li >med - more verbose listing with license, description, etc.</li>
<li >detail - lots of details about upload including remixes and sources</li>
<li >info - same as detail but without upload name and artist (perfect for little blue 'i' buttons)</li>
<li >mplayer - Fabricios MP3 Flash (tm) player, single line version</li>
<li >mplayerbig - same as mplayer but bigger</li>
</ul>
</td>
<td  class="def">med
</td>
</tr>
<tr >
<td >  macro, m
</td>
<td >  Return records formatted by the specified macro from the current skin. For format=page only. 
</td>
<td  class="def">  list_records
</td>
</tr>
<tr >
<td >  tmacro
</td>
<td >  Return records formatted by the specific template and macro.
e.g. mytemplate.xml/mymacro The template file must be in the 'Skin' ccHost path. For format=page only. 
</td>
<td  class="def">  uploads.xml/<br  />list_records
</td>
</tr>
<tr >
<td >  template_tags
</td>
<td >  For format=html, docwrite and page only. (Programmable interface only) Name/value touples sent into the template. 
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  title
</td>
<td >  For format=page only. Display value as page title and browser caption.
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  feed
</td>
<td >  For format=page only. Display value as feed button's caption.
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  vroot
</td>
<td >  Return records that were uploaded only in this virtual root. (ex. vroot=magnatune)
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  chop
</td>
<td >  Long upload names and user names are truncated at this length (only used by formats html, docwrite and list).
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  playlist
</td>
<td >  Returns records that are currently assigned to a playlist identified by the playlist 'id'. For dynamic
    playlists the internal query is merged with other arguments
</td>
<td  class="def">
</td>
</tr>
<tr >
<td >  cols
</td>
<td >  Only return certain columns from database. Possible values are comma separated single characters from
the following list:
  <ul >
<li >a - number of upload scores (ratings)</li>
<li >b - total number of remixes</li>
<li >c - total number of sources used</li>
<li >d - upload date</li>
<li >i - upload id</li>
<li >l - (lower case 'L') short license name</li>
<li >n - upload name</li>
<li >r - artist 'real' name (as opposed to login name)</li>
<li >s - upload score (rating)</li>
<li >t - comma separated upload tags</li>
<li >u - artist (user) login name</li>
</ul>
Specifying columns this way is very efficient however will not work with most of pre-formatted templates. Only AJAX
and template developers should even think about using this parameter.
</td>
<td  class="def">
</td>
</tr>
</tbody>
</table>
<h2 > Output Formats </h2>
<p >The following is the list of currently known values for the 'format' input parameter:</p>
<table  id="ftable" border="0" cellpadding="0" cellspacing="0">
<tbody >
<tr >
<td  width="10%"> php
</td>
<td > Returns records in an array.
</td>
<td > n/a
</td>
</tr>
<tr >
<td  width="10%"> phps
</td>
<td > Returns records as a php serialized string
</td>
<td > text/plain
</td>
</tr>
<tr >
<td  width="10%"> count
</td>
<td > Return only the count of the records for the query in the form: [count]
</td>
<td > text/plain
</td>
</tr>
<tr >
<td  width="10%"> ids
</td>
<td > Return only the upload IDs as a semi-colon list
</td>
<td > text/plain
</td>
</tr>
<tr >
<td  width="10%"> js
</td>
<td > Records returned in JSON format as an array of objects. Care should
be taken because records can be get large, it's easy to get eat up a browser's 
memory with more than a few records. 
Below is the layout (there might be 
arrays nested in fields):
<span  class="cc_code">
 [{field:value,field:value...},{field:value,...}]
</span>
</td>
<td > application/javascript
</td>
</tr>
<tr >
<td  width="10%"> json
</td>
<td > Same as js but the value will be in a X-JSON header, not the body. Again, severe memory limitations
should be considered here. (Firefox in particular will simply not return
anything in the JSON header when it reaches just a few records worth of data.)
</td>
<td > application/javascript
</td>
</tr>
<tr >
<td  width="10%"> csv
</td>
<td >
<a  href="http://www.creativyst.com/Doc/Articles/CSV/CSV01.htm#FileFormat" title="Comma separated text">Comma separated text</a> &nbsp;
</td>
<td > text/plain
</td>
</tr>
<tr >
<td  width="10%"> rss
</td>
<td > RSS 2.0
</td>
<td > text/xml+rss
</td>
</tr>
<tr >
<td  width="10%"> atom
</td>
<td > ATOM
</td>
<td > text/xml+atom
</td>
</tr>
<tr >
<td  width="10%"> xspf
</td>
<td > XSPF
</td>
<td > text/xml+xspf
</td>
</tr>
<tr >
<td  width="10%"> html
</td>
<td > Returns records formatted as HTML as determined by the 'template' input parameter.
</td>
<td > text/html
</td>
</tr>
<tr >
<td  width="10%"> docwrite
</td>
<td > Same as html but wraps all lines in javascript document.write. Useful for including
 &lt;script&gt; tags.
</td>
<td > application/javascript
</td>
</tr>
<tr >
<td  width="10%"> page
</td>
<td > Returns an entire page in the current skin listing the records. A 'macro' parameter
means use the current skin's version of that macro. A 'tmacro' parameter can be used to
specify a macro in a template file not in the current skin. Without either the default
listing macro for that skin is used.
</td>
<td > text/html
</td>
</tr>
<tr >
<td  width="10%"> playlist
</td>
<td > Returns an entire page in 'playlist format'. See ccMixter for what that looks like. (This is
how Dynamic Playlists are created: <a  href="http://ccmixter.org/media/api/query?remixesof=musetta&f=playlist">api/query?remixesof=musetta&f=playlist</a>) 
</td>
<td > text/html
</td>
</tr>
</tbody>
</table>
<h2 > Examples </h2>
<h3 > URL in ccHost</h3>
<p >
Leaving off all formatting will result in a 'page' formatting in the ccHost site. 
From the browser the input parameters can be either a URI query string (GET) or form fields and values (POST) send to the "api/query" command:
</p>
<div  class="code">
  /media/api/query?limit=5&tags=remix+chill&amp;sort=name
</div>
<p ></p>

Return the last 5 five upload by teru:
<div  class="code">
  ?<b >user</b>=teru&amp;<b >limit</b>=5&titla=terus latest 5
</div>

Return all the remixes of brad sucks since the last time we checked:
<div  class="code">
 ?<b >remixesof</b>=bradsucks&amp;<b >f</b>=rss&amp;<b >sinced</b>=2006-10-22+15:24GMT
</div>
<h3 > Embedded in HTML (a.k.a. "Publicize") </h3>
<p >To embed a query and results into an existing HTML page at another site user the 'docwrite' format which can 
be used from a &lt;script&gt; tag as the "src" attribute:
</p>
<div  class="code">
  &lt;script type="text/javascript"<br  />
src="http://ccmixter.org/media/api/query?limit=5&tags=remix&amp;sort=rank&amp;ord=desc&amp;f=docwrite&amp;t=linksf=html&amp;chop=12"&gt;
<br  />  &lt;/script&gt;
</div>
<p >Results from ccMixter:</p>
<div  style="width:70%;margin-left: 30px;">
<script  type="text/javascript" src="http://ccmixter.org/media/api/query?limit=5&tags=remix+chill&sort=rank&ord=desc&f=docwrite&t=links">
</script>
</div>
<h3 > AJAX (using prototype.js) </h3>
<p >AJAX's native request format is already URI's and the Query Engine returns JSON as optional format
so the fit is very natural. (You can also request HTML format and that works quite nicely too.)
</p>
<p >JSON can be very retrictive in how much will actually show up in the browser so using the 'cols'
parameter and setting a now limit is highly advised.</p>
<div  class="code" style="white-space: pre">
  &lt;div id="results"&gt;&lt;/div&gt;
  &lt;script&gt;
    var my_query_requester = {

      put_results_here: '',

      get_records: function(tags,results_element) 
      {
        this.put_results_here = results_element;

          // get the five latest uploads for these tags...
          var url = 'http://ccmixter.org/media/api/query?f=json&amp;limit=5&amp;cols=u,n,r&amp;tags=' + tags;
          new Ajax.Request( url, { method: 'get', onComplete: this.got_data.bind(this) } );
      },

      got_data: function(resp,json) {
            var html = json.inject( '', function(str,record) {
              return str + '&lt;li&gt;' + record.n + 
                         ' by &lt;a href="http://ccmixter.org/media/people/' + record.u +'"&gt;' +
                         record.r + '&lt;/a&gt;&lt;/li&gt;';
            });

            $(this.put_results_here).innerHTML = '&lt;ul&gt;' + html + '&lt;/ul&gt;';
      }
  }

  my_query_requester.get_records('female_vocals,remix','results');
  &lt;/script&gt;
</div>
<p >NOTE: Performing cross domain AJAX involves a certain amount of <a  href="http://google.com/search?q=cross domain ajax">black magic</a>.</p>
<h3 > Feed URLs </h3>
<p >
All feed URIs are 'query enabled' which means most input parameters will work the same as a call to api/query with the notable exception that the 'format' parameter is ignored.
</p>
<p >
These URIs return exactly the same thing (funky remixes of teru in ATOM format):
</p>
<div  class="code">
/media/api/query?f=atom&remixesof=teru&tags=funky<br  /><br  />
/media/feed/atom/funky?remixesof=teru
</div>

Here we return a radio station style playlist of chill in XSPF format
<div  class="code">
  ?<b >rand</b>=1&amp;<b >f</b>=xspf&amp;<b >tags</b>=cill+ambient&amp;<b >type</b>=any&amp;<b >reqtags</b>=audio&amp;<b >promo_tag</b>=site_promo
</div>
<h3 > Stream URLs </h3>
<p >Streaming is slightly special case because there are several M3U format players that will only
response to 'files' with a '.m3u' extension. Adding a 'fake' file name to the query URL will be
ignored so the safest thing to is something like:</p>
<div  class="code">
/media/api/query/<b >fake.m3u</b>?f=m3u&remixesof=teru&tags=funky<br  />
</div>
<h3 > Programmatic </h3>
<p >From code you use the CCQuery object's Query method passing parameters in a single array:</p>
<div  class="code">parse_str('tags=chill&amp;sort=rank&amp;ord=desc&amp;format=php;', $args);
<br  />
 $query = new <b >CCQuery</b>();
<br  />
 list( $results, ) = $query-&gt;<b >Query</b>($args);
 </div> 

 
To combine arguments with browser REQUEST parameters, call the method ProcessUriArgs first

<div  class="code"> 
  $args = $query-&gt;<b >ProcessUriArgs</b>($args); // your args have precendent
  <br  />
  list( $results, ) = $query-&gt;Query($args);
</div>
<h3 > phpTAL Template </h3>
<p >
From ccHost templates use <b >cc_query_fmt</b> to get the records returned:
</p>
<div  class="code" style="white-space:pre">
&lt;tal:block define="records php:<b >cc_query_fmt</b>('tags=remix,chill&sort=name');" /&gt;
&lt;div tal:repeat="record records"&gt;
   &lt;a href="$<?= $A['record']['file_page_url']?>" &gt;$<?= $A['record']['upload_name']?>&lt/a&gt;
   by 
   &lt;a href="$<?= $A['record']['artist_page_url']?>" &gt;$<?= $A['record']['user_real_name']?>&lt/a&gt;
&lt;/div&gt;
</div>
<div  style="font-style:italic">Last updated: 2/12/2007 </div>