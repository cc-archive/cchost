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

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
* Bass class for use by singleton table representations 
*
* 
*
*/
class CCTable
{
    var $_table_name;
    var $_key_field;
    var $_joins;
    var $_join_num;
    var $_join_parts;
    var $_extra_columns;
    var $_order;
    var $_direction;
    var $_last_sql;
    var $_offset;
    var $_limit;
    var $_group_on_key;

    /**
    * Constructor
    *
    * @param string $table_name Name in the mySQL database
    * @param string $key_field  Field to use in JOINs and key lookups
    */
    function CCTable($table_name,$key_field)
    {
        $this->_table_name = $table_name;
        $this->_key_field = $key_field;
        $this->_joins = array();
        $this->_join_num = 1;
        $this->_group_on_key = false;
    }

    /**
    * Set the sort order for the next Query
    *
    * @param string $order_expression Typically a column but can be any valid mySQL expressions
    * @param string $dir Either ASC or DESC
    */
    function SetSort($order_expression, $dir = 'ASC')
    {
        $this->_order = $order_expression;
        $this->_direction = $dir;
    }

    /**
    * Alias for SetSort
    *
    * @param string $order_expression Typically a column but can be any valid mySQL expressions
    * @param string $dir Either ASC or DESC
    * @see SetSort
    */
    function SetOrder($order_expression, $dir = 'ASC')
    {
        $this->SetSort($order_expression, $dir);
    }

    /**
    * Set row offset and limit number of rows returned from queries
    *
    * Set both parameters to 0 to reset.
    *
    * @param integer $offset Row number to start query
    * @param integer $limit  Maximun number of rows to return
    */
    function SetOffsetAndLimit($offset,$limit)
    {
        $this->_offset = $offset;
        $this->_limit  = $limit;
    }

    function GroupOnKey($on=true)
    {
        $this->_group_on_key = $on;
    }

    /**
    * For use by derived class, adds a column to SELECT statement.
    * 
    * This method is useful to formatting fields such as date or numerics.
    * 
    * In a derived class' contstructor:
    * 
    * <code>
    *    $this->AddExtraColumn('DATE_FORMAT(upload_date, \'$CC_SQL_DATE\') as upload_date_format');
    * </code>
    *
    * @param string $spec Valid mySQL string for defining a virtual column
    */
    function AddExtraColumn($spec)
    {
        if( empty($this->_extra_columns) )
            $this->_extra_columns = array();

        $this->_extra_columns[] = $spec;
    }

    function RemoveExtraColumn($spec)
    {
        $this->_extra_columns = array_diff($this->_extra_columns,array($spec));
    }

    /**
    * For use by derived class, adds a JOIN to SELECT statement.
    * 
    * This method is useful to expanding the information returned by Query()
    * 
    * In a derived class' contstructor:
    * 
    * <code>
    *            $this->AddJoin( new CCUsers(),    'upload_user');
    * </code>
    * 
    * @param string $other_cctable Instance of a CCTable class to JOIN with
    * @param string $joinfield Name of field in <b>this</b> table that the key field of the <b>other</b> table will join on.
    * @param string $jointype Valid mySQL type of JOIN 
    * @returns mixed $name JOIN token. Hold onto this and pass to RemoveJoin if you don't want the next Query() to use the join.
    * @see RemoveJoin
    */
    function AddJoin($other_cctable, $joinfield, $jointype = 'LEFT OUTER' )
    {
        $name      = 'j' . $this->_join_num++;
        $othername = $other_cctable->_table_name;
        $otherkey  = $other_cctable->_key_field;

        if( !strstr($joinfield,'.') )
            $jfield = $this->_table_name . '.' . $joinfield;
        else
            $jfield = $joinfield;

        $join = "\n $jointype JOIN $othername $name ON $jfield = $name.$otherkey ";

        $this->_joins[$name] = $join;
        $this->_join_parts[$name] = array( $other_cctable,
                                           $joinfield);
        return($name);
    }

    /**
    * For use by derived class, removed a previously added JOIN to SELECT statement.
    * 
    * This method will remove a JOIN placed on queries by AddJoin
    * 
    * @param mixed $joinid Token returned from AddJoin()
    * @see AddJoin
    */
    function RemoveJoin($joinid)
    {
        unset($this->_joins[$joinid]);
        unset($this->_join_parts[$joinid]);
    }

    function FakeJoin(&$fake_record)
    {
        foreach( $this->_join_parts as $jpart )
        {
            if( !empty($fake_record[$jpart[1]]) )
            {
                $table = $jpart[0];
                $fake_record += $table->QueryKeyRow($fake_record[$jpart[1]]);
            }
        }

    }

    /**
    * Get an instance of this table.
    *
    * This method is abstract (not actually implemented here) static call
    * implemented by derived classes to return a singleton instance of themselves.
    * 
    * Tables should <i>rarely</i> be instantiated with 'new'. Instead, call GetTable()
    * to get a singleton instance of the class:
    * 
    * <code>
    *     $uploads =& CCUploads::GetTable();
    * </code>
    * 
    * Where CCUploads is a derivation of CCTable.
    * @return object $table Returns a singleton instance of this object.
    */
    function GetTable()
    {
    }

    /**
    * Query from the virtual table
    * 
    * The $where parameter can either be a mySQL WHERE clause (without the WHERE)
    * or it can be an array where the key is the column name and the value is what 
    * to test for.
    * 
    * <code>
    *      // The following is the equivalent of:
    *      //   ...WHERE ('file_name' = 'myfile.mp3') AND ('user' = 204 )
    *      
    *      $where['file_name'] = 'myfile.mp3';
    *      $where['user']      = 204;
    *      $sql_result =& $table->Query($where);
    * </code>
    * 
    * @param mixed $where mySQL WHERE clause as string or array 
    * @return mixed $query_results Results of mySQL query
    */
    function Query($where ='')
    {
        return( CCDatabase::Query(  $this->_get_select($where) ) );
    }

    /**
    * Convert database 'rows' to a more semantically rich 'records'
    * 
    * @param array &$rows Rows as retrieved from the database
    * @return array $records Records that has runtime formatted data
    */
    function & GetRecordsFromRows(&$rows)
    {
        $records = array();
        $count = count($rows);
        for( $i = 0; $i < $count; $i++ )
            $records[] = $this->GetRecordFromRow($rows[$i]);
        return $records;
    }

    /**
    * Convert a database 'row' to a more semantically rich 'record'
    * 
    * This method is abstract (returns $row). Derived classes
    * implement this method for shortly after a row from the database has
    * been returned to fill the row with semantically rich, runtime data.
    * 
    * @param array $row Row as retrieved from the database
    * @return array $record A 'record' that has runtime data
    */
    function & GetRecordFromRow( &$row )
    {
        return $row;
    }

    function & GetRecordsFromKeys($keys)
    {
        $where = array();
        $name = $this->_key_field;
        foreach( $keys as $key)
            $where[] = "( $name = '$key' )";
        $where = implode( ' OR ', $where );
        $records =& $this->GetRecords($where);
        return $records;
    }

    function & GetRecords($where)
    {
        $rows = $this->QueryRows($where);
        $records =& $this->GetRecordsFromRows($rows);
        return $records;
    }

    /**
    * Yes, same as GetRecordFromKey
    */
    function & GetRecordFromID($key)
    {
        $row = $this->QueryKeyRow($key);
        return( $this->GetRecordFromRow($row) );
    }

    /**
    * Yes, same as GetRecordFromID
    */
    function & GetRecordFromKey($key)
    {
        $row = $this->QueryKeyRow($key);
        return( $this->GetRecordFromRow($row) );
    }

    /**
    * For use by derived class if they implement _get_select
    * 
    * This method verifies the WHERE part of a mySQL query. It should
    * only be used if a derived class 
    * 
    * @param mixed $where either query string or array of 'column' => 'value' to test for
    * @return string $where_clause WHERE clause (without the WHERE)
    */
    function _where_to_string($where)
    {
        if( empty($where) )
            return($where);

        if( is_string($where) )
            return($where);

        if( is_array($where) )
        {
            $str = '(';
            foreach( $where as $K => $V )
                $str .= "($K = '" . addslashes($V) . "') AND";

            $where = substr($str,0,-4) . ') ';
        }

        return($where);
    }

    /**
    * Internal helper that actually constructs SELECT statements
    *
    * @param mixed $where string or array representing WHERE clause
    * @param string $columns SELECT will be limited to these columns
    * @return string $select Fully formed SELECT statement
    */
    function _get_select($where,$columns='*')
    {
        $where = $this->_where_to_string($where);

        if( $where )
            $where = "WHERE $where";

        $order = '';
        if( $this->_order )
            $order = 'ORDER BY ' . $this->_order . ' ' . $this->_direction;

        $extra = '';
        if( $columns == '*' && $this->_extra_columns )
            $extra = ',' . implode(',',$this->_extra_columns);
        $join = implode(' ', $this->_joins);
        $group = $this->_group_on_key ? "\nGROUP BY " . $this->_key_field : '';
        $sql = "SELECT $columns $extra \nFROM $this->_table_name \n $join \n $where $group \n $order";

        $this->_add_offset_limit($sql);

        if( CCDebug::IsEnabled() )
            $this->_last_sql = $sql;

        return($sql);
    }

    /**
    * Internal helper to add OFFSET and LIMIT quota to SELECT statements
    *
    * @param string $sql A reference to the current SELECT statment to be appended
    */
    function _add_offset_limit(&$sql)
    {
        if( empty($this->_offset) && empty($this->_limit) )
            return;

        $sql .= " LIMIT " . $this->_limit;

        if( !empty($this->_offset) )
            $sql .= " OFFSET " . $this->_offset;
    }

    /**
    * Return the value of a single item.
    * 
    * <code>
    *    $where['user_id'] = 10;
    *    $name = $table->QueryItem('user_name', $where );
    * </code>
    * 
    * @param string $column_name Name of table's column
    * @param mixed $where string or array representing WHERE clause
    * @see Query
    * @return mixed $item Item from database 
    */
    function QueryItem($column_name,$where)
    {
        $sql = $this->_get_select($where,$column_name);
        return( CCDatabase::QueryItem($sql) );
    }

    /**
    * Return the key for a record that matches the $where clause
    * 
    * <code>
    *    $where['user_name'] = 'Fred';
    *    $key = $table->QueryKey($where );
    * </code>
    * 
    * @param mixed $where string or array representing WHERE clause
    * @see Query
    * @return mixed $key Item from database, typically a primary key number
    */
    function QueryKey($where)
    {
        return( $this->QueryItem( $this->_key_field, $where ) );
    }
    
    function QueryKeys($where='')
    {
        $sql = $this->_get_select($where,$this->_key_field);
        $qr = CCDatabase::Query($sql);
        $keys = array();
        while( $r = mysql_fetch_row($qr) )
            $keys[] = $r[0];
        return($keys);
    }

    /**
    * Return the row for the record where key is $key
    * 
    * The constructor of this class determines what the key column
    * is.
    * 
    * <code>
    *    // return the row the record whose key value is '4501'
    *    $row =& $table->QueryKeyRow(4501);
    * </code>
    * 
    * @param string $key Key value
    * @see CCTable
    * @return array $row Row from database
    */
    function QueryKeyRow($key)
    {
        return( $this->QueryRow( $this->_key_field . " = '$key'" ) );
    }

    /**
    * Return an item from the record where key is $key
    * 
    * <code>
    *    $name = $table->QueryItemFromKey('user_name',1058);
    * </code>
    * 
    * @param string $column_name Name of table's column
    * @param string $key Key value
    * @return mixed $item Item from database 
    */
    function QueryItemFromKey($column_name,$key)
    {
        return( $this->QueryItem( $column_name, $this->_key_field . " = '$key'" ) );
    }

    /**
    * Returns a single row that matches a query
    * 
    * <code>
    *    $where['user_name'] = 'Fred';
    *    $row =& $table->QueryRow($where );
    * </code>
    * 
    * @param mixed $where string or array representing WHERE clause
    * @return array $row Row from database
    * @see Query
    */
    function QueryRow($where)
    {
        return( CCDatabase::QueryRow( $this->_get_select($where) ) );
    }

    /**
    * Returns an array  of rows that matches a query
    * 
    * <code>
    *    $where['user_name'] = 'Fred';
    *    $rows =& $table->QueryRows($where );
    *    foreach( $rows as $row )
    *    {
    *       // ....
    *    }
    * </code>
    * 
    * @param mixed $where string or array representing WHERE clause
    * @return array $row Array of rows from database
    * @see Query
    */
    function QueryRows($where,$columns='*')
    {
        return( CCDatabase::QueryRows(  $this->_get_select($where,$columns) ) );
    }

    function CountRows($where = '' )
    {
        if( $this->_group_on_key )
        {
            $sql = $this->_get_select($where,'COUNT(*)');
            $rows = CCDatabase::QueryRows($sql,false);
            return( count($rows) );
        }
        return( $this->QueryItem("COUNT(*)",$where) );
    }

    function Max($column, $where = '' )
    {
        return( $this->QueryItem("MAX($column)",$where) );
    }

    function Insert($fields)
    {
        $columns  = array_keys($fields);
        $data     = array_values($fields);
        $cols     = implode( ',', $columns );
        $count    = count($data);
        $values   = '';
        for( $i = 0; $i < $count; $i++ )
            $values .= " '" . addslashes($data[$i]) . "', ";
        $values   = substr($values,0,-2);
        $sql = "INSERT INTO {$this->_table_name} ($cols) VALUES ( $values )";
        CCDatabase::Query($sql);
    }

    function InsertBatch($columns,$values)
    {
        $valuestr = '';
        foreach( $values as $valuefields )
        {
            $valuestr .= '( ';
            foreach( $valuefields as $value )
            {
                $valuestr .= "'" . addslashes($value) . "', ";
            }
            $valuestr = substr($valuestr,0,-2) . '), ';
        }
        $cols = implode(',',$columns);
        $sql = "INSERT INTO {$this->_table_name} ($cols) VALUES " . substr($valuestr,0,-2);
        CCDatabase::Query($sql);
   }

    function Update($fields,$autoquote=true)
    {
        $this->UpdateWhere($fields, $this->_key_field . "= '{$fields[$this->_key_field]}'",$autoquote);
    }

    function UpdateWhere($fields,$where,$autoquote=true)
    {
        $sets = '';        
        foreach( $fields as $k => $v )
        {
            $v = addslashes($v);
            if( $autoquote )
                $v = "'$v'";
            $sets .= " $k = $v, ";
        }
        if( !empty($where) )
            $where = ' WHERE ' . $this->_where_to_string($where);
        else
            $where = '';
        $sql = "UPDATE $this->_table_name SET " . substr($sets,0,-2) . $where;
        CCDatabase::Query($sql);
        $this->_last_sql = $sql;
    }

    function DeleteKey($key)
    {
        $key = addslashes($key);
        $this->DeleteWhere($this->_key_fields . "= '$key'");
    }

    function DeleteWhere($where)
    {
        $where = $this->_where_to_string($where);
        $sql = "DELETE FROM $this->_table_name WHERE $where";
        CCDatabase::Query($sql);
    }

    function NextID()
    {
        $sql = "SHOW TABLE STATUS LIKE '$this->_table_name'" ;
        $row = CCDatabase::QueryRow($sql);
        return( $row['Auto_increment'] );
    }

    /**
    * Returns strings usable for searching serialized PHP strings
    * 
    * Returns an array with two strings in it:
    *   first string is usable as a field in SELECT and WHERE statements
    *   second string is usable in WHERE statements for regex matching
    * 
    * <code>
        
        $arr = serialize( array( 'foo' => 'bar', 'birthday' => '2004-01-29' ) );
        $where['s_field'] = $arr;
        $table->Insert($where);

        // then...
        // query for anything but baz
        array( $s_field ) = $table->WhereForSerializedField('s_field','foo');
        $qwhere = "$s_field <> 'baz'";
        $rows = $table->QueryRows($qwhere);

        // or...
        // query for today's birthdays
        array( , $s_regex_where ) = $table->WhereForSerializedField('s_field','foo','[0-9]+-01-29');
        $rows = $table->QueryRows($s_regex_where);

    * </code>        
    * 
    * The selects drawn from this is potentially a very slow operation so use sparingly.
    * 
    * NB: This has only been tested with 
    * @param string $dbcol Name of serialized data column
    * @param string $fname Name of field in serialized 
    * @param string $matches Regex expression to match
    * @returns array as described above
    */
    function WhereForSerializedField($dbcol, $fname, $matches = '[^"]+')
    {
        $locator = 's:' . strlen($fname) . ':"' . $fname . '";';
        $loc_len = strlen($locator);
        $locate  = "LOCATE('$locator',$dbcol)";
        $len_pos = $loc_len + 2;
        $len_len = "LOCATE(':', SUBSTRING( $dbcol, $locate + $len_pos, 5  )) - 1";
        $val_len = "SUBSTRING( $dbcol, $locate + $len_pos, $len_len)";
        $val_pos = "($len_pos + $len_len + 2)";
        $where   = '(' . $dbcol . ' REGEXP \'' . $locator . '.:[1-9]+:"' . $matches . '"\')';
        $field   = "SUBSTRING( $dbcol, $locate + $val_pos, $val_len )";

        return ( array( $field, $where )  );
    }

}
 
?>