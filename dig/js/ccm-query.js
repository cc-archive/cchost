/*
* Artistech Media has made the contents of this file
* available under a CC-GNU-GPL license:
*
* http://creativecommons.org/licenses/GPL/2.0/
*
* A copy of the full license can be found as part of this
* distribution in the file LICENSE.TXT.
* 
* You may use dig.ccMixter software in accordance with the
* terms of that license. You agree that you are solely 
* responsible for your use of dig.ccMixter software and you
* represent and warrant to Artistech Media that your use
* of dig.ccMixter software will comply with the CC-GNU-GPL.
*
* $Id$
*
*/

function makeA(iterable) {
  if (!iterable) return [];
  if (iterable.toArray) {
    return iterable.toArray();
  } else {
    var results = [];
    for (var i = 0, length = iterable.length; i < length; i++)
      results.push(iterable[i]);
    return results;
  }
}

var ccmClass = {
  create: function() {
    return function() {
      this.initialize.apply(this, arguments);
    }
  }
}

Function.prototype.bind = function() {
  var __method = this, args = makeA(arguments), object = args.shift();
  return function() {
    return __method.apply(object, args.concat(makeA(arguments)));
  }
}

function d(str)
{
    $('#debug').prepend(str + '<br />');
}
function dclear()
{
  $('#debug').html('&nbsp;');
}
function dlink(url)
{
    var str = '<a href="' + url + '">' + url + '</a>';
    d(str);
}
function dobj(obj)
{
    for( f in obj )
    {
      if( typeof(obj[f]) == 'object' )
      {
        dobj(obj[f]);
      }
      else if( typeof[obj[f]] == 'function' )
      {
        d( '[' + f + '] ' + '(function)' )
      }
      else
      {
        d( '[' + f + '] ' + obj[f] );
      }
    }
}

var ccmQuery = ccmClass.create();

ccmQuery.prototype = {

    // ctor - don't call initialize(), call this way:
    //
    //  new ccmQuery(options, parameters, function(){ ...} )
    //
    // options = {
    //     paging: true|false,                   // default is false
    //     parent: 'YOUR_PARENT_SELECTOR',   // container selector for this query
    //     debug: true|false                 // dumps ajax URL right before calling
    //  }
    //
    // parameters = {
    //     param name: default value,
    //     ....
    //  }
    // 
    // param names that have matching form fields on the page (contained 
    // in options.parent) will take precidence for query. For example:
    //
    //    options = {
    //        parent: 'myquery'
    //    }
    //    parameters = {
    //        tags: '',
    //        sort: 'name'
    //      }
    //
    //  where on the page is:
    //
    //     <div id="myquery">
    //       <input id="tags" />
    //     </div>
    //
    //  Will use the value in #myquery #tags for the query, but 'name' for the sort parameter
    //  
    //  If there is there is a matching form field, then the value in parameters{} is treated as
    //  default value and if it matches the val() in the form field then that parameter is NOT sent
    //  to the query.
    //
    initialize: function(options, fields, func) {
        this._options = options;
        this._parameters = fields;
        this._user_func = func;
        if( !this._options.mode )
          this._options.mode = 'ajax';          
    },

    // public
    
    query: function () {
      
      this._clear_values();
      this.values.num_results = 0;

      this._get_params();
      
      if( this._options.paging && !this._count_fetched && (this._options.mode == 'ajax') )
        this._call_ccm( 'count', this._on_count_return.bind(this) );
      else
        this._call_ccm( 'js', this._on_query_return.bind(this) );
    },
    
    page: function(dir) {
        var newOffs = parseInt(this.values.offset) + (dir * parseInt(this.values.limit));
        if( newOffs < 0 )
            newOffs = 0;
        this.values.offset = this._parameters.offset = newOffs;
        this._get_params();
        this.query();
    },

    // query values filled in after a query is made.
    values: {},

    // private
    
    _rootURL: QUERY_ROOT_URL,
    _proxyURL: QUERY_PROXY_URL, 
    _count_fetched: false,
    
    _parameters: {},
    
    _clear_values: function()
    {
      if( this._count_fetched )
      {
        var cnt = this.values.total;
        this.values = {};
        this.values.total = cnt;
      }
      else
      {
        this.values = {};
      }
    },
    
    _flatten_obj: function(obj,target,path)
    {
      // wtf was I thinking ??
      
      /*
       
        var pathChar = path ? '/' : '';
    
        for( field in obj )
        {
            var this_path = path + pathChar + field;
            
            if( typeof(obj[field]) == 'object' )
            {
                target = this._flatten_obj(obj[field],target,this_path)
            }
            else
            {
                target[this_path] = obj[field];
            }
        }
        
        return target;
      */
      
      return obj;
    },

    _on_count_return: function(data)
    {
        // data from 'count' format is
        // returned in an array()
        var val = eval(data);
        this.values.total = parseInt(val[0]);
        this._count_fetched = true;
        this._call_ccm( 'js', this._on_query_return.bind(this) );
    },
    
    _on_query_return: function(data)
    {
        var resp = eval(data);
        var targets = [];
        var i;
        for( i = 0; i < resp.length; i++  )
        {
            var target = {};
            var obj = resp[i];
            if( obj.files )
                obj.num_files = obj.files.length;
            target = this._flatten_obj(obj,target,'');
            targets.push(target);
        }
    
        this.values.num_results = targets.length;
        if( this._options.paging )
        {
          this.values.limit  = parseInt(this.values.limit);
          this.values.offset = parseInt(this.values.offset);
        }
        
        this._user_func(targets);
    },

    _get_params: function() {
        this._params = '';
        var parent = this._options.parent ? $(this._options.parent) : null;
        for( var f in this._parameters )
        {
            var field = $( '#' + f, parent );
            var val = null;
            var is_field = false;
            
            if( field && field.val )
            {
              var val_test = field.val();
              if( typeof(val_test) != 'undefined' )
              {
                is_field = true;
                if( val_test != this._parameters[f] )
                  val = val_test
              }
            }
            
            if( !is_field )
            {
              val = this._parameters[f];
            }
            if( val )
                this._params += '&' + f + '=' + val;
            this.values[f] = val;
        }
    },
    
    _call_ccm: function( format, func ) {    

        switch( this._options.mode )
        {
          case 'ajax':
            {
              var url = this._rootURL + 'f=' + format + this._params;
              
              if( this._proxyURL )
              {
                  url = this._proxyURL + escape(url);
              }
      
              if( this._options.debug )
                dlink(url);
                  
          
              jQuery.ajax({
                          type: "POST",
                          url: url,
                          data: {},
                          dataType: 'html',
                          success: func
                          });
              
              break;
            }
            
          case 'server':
          case 'remote':
            {
              var url = '/' + this._options.doc + '?' + this._params;
              document.location = url;
              break;
            }
        }
    }
}
