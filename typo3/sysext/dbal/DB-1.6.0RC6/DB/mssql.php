<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Sterling Hughes <sterling@php.net>                           |
// | Maintainer: Daniel Convissor <danielc@php.net>                       |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'DB/common.php';

/**
 * Database independent query interface definition for PHP's Microsoft SQL Server
 * extension.
 *
 * @package  DB
 * @version  $Id$
 * @category Database
 * @author   Sterling Hughes <sterling@php.net>
 */
class DB_mssql extends DB_common
{
    // {{{ properties

    var $connection;
    var $phptype, $dbsyntax;
    var $prepare_tokens = array();
    var $prepare_types = array();
    var $transaction_opcount = 0;
    var $autocommit = true;
    var $_db = null;

    // }}}
    // {{{ constructor

    function DB_mssql()
    {
        $this->DB_common();
        $this->phptype = 'mssql';
        $this->dbsyntax = 'mssql';
        $this->features = array(
            'prepare' => false,
            'pconnect' => true,
            'transactions' => true,
            'limit' => 'emulate'
        );
        // XXX Add here error codes ie: 'S100E' => DB_ERROR_SYNTAX
        $this->errorcode_map = array(
            170   => DB_ERROR_SYNTAX,
            207   => DB_ERROR_NOSUCHFIELD,
            208   => DB_ERROR_NOSUCHTABLE,
            245   => DB_ERROR_INVALID_NUMBER,
            547   => DB_ERROR_CONSTRAINT,
            2627  => DB_ERROR_CONSTRAINT,
            2714  => DB_ERROR_ALREADY_EXISTS,
            3701  => DB_ERROR_NOSUCHTABLE,
            8134  => DB_ERROR_DIVZERO,
        );
    }

    // }}}
    // {{{ connect()

    function connect($dsninfo, $persistent = false)
    {
        if (!DB::assertExtension('mssql') && !DB::assertExtension('sybase')
            && !DB::assertExtension('sybase_ct'))
        {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }
        $this->dsn = $dsninfo;
        $dbhost = $dsninfo['hostspec'] ? $dsninfo['hostspec'] : 'localhost';
        $dbhost .= $dsninfo['port'] ? ':' . $dsninfo['port'] : '';

        $connect_function = $persistent ? 'mssql_pconnect' : 'mssql_connect';

        if ($dbhost && $dsninfo['username'] && $dsninfo['password']) {
            $conn = @$connect_function($dbhost, $dsninfo['username'],
                                       $dsninfo['password']);
        } elseif ($dbhost && $dsninfo['username']) {
            $conn = @$connect_function($dbhost, $dsninfo['username']);
        } else {
            $conn = @$connect_function($dbhost);
        }
        if (!$conn) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED, null, null,
                                         null, mssql_get_last_message());
        }
        if ($dsninfo['database']) {
            if (!@mssql_select_db($dsninfo['database'], $conn)) {
                return $this->raiseError(DB_ERROR_NODBSELECTED, null, null,
                                         null, mssql_get_last_message());
            }
            $this->_db = $dsninfo['database'];
        }
        $this->connection = $conn;
        return DB_OK;
    }

    // }}}
    // {{{ disconnect()

    function disconnect()
    {
        $ret = @mssql_close($this->connection);
        $this->connection = null;
        return $ret;
    }

    // }}}
    // {{{ simpleQuery()

    function simpleQuery($query)
    {
        $ismanip = DB::isManip($query);
        $this->last_query = $query;
        if (!@mssql_select_db($this->_db, $this->connection)) {
            return $this->mssqlRaiseError(DB_ERROR_NODBSELECTED);
        }
        $query = $this->modifyQuery($query);
        if (!$this->autocommit && $ismanip) {
            if ($this->transaction_opcount == 0) {
                $result = @mssql_query('BEGIN TRAN', $this->connection);
                if (!$result) {
                    return $this->mssqlRaiseError();
                }
            }
            $this->transaction_opcount++;
        }
        $result = @mssql_query($query, $this->connection);
        if (!$result) {
            return $this->mssqlRaiseError();
        }
        // Determine which queries that should return data, and which
        // should return an error code only.
        return $ismanip ? DB_OK : $result;
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal mssql result pointer to the next available result
     *
     * @param a valid fbsql result resource
     *
     * @access public
     *
     * @return true if a result is available otherwise return false
     */
    function nextResult($result)
    {
        return mssql_next_result($result);
    }

    // }}}
    // {{{ fetchInto()

    /**
     * Fetch a row and insert the data into an existing array.
     *
     * Formating of the array and the data therein are configurable.
     * See DB_result::fetchInto() for more information.
     *
     * @param resource $result    query result identifier
     * @param array    $arr       (reference) array where data from the row
     *                            should be placed
     * @param int      $fetchmode how the resulting array should be indexed
     * @param int      $rownum    the row number to fetch
     *
     * @return mixed DB_OK on success, NULL when end of result set is
     *               reached or on failure
     *
     * @see DB_result::fetchInto()
     * @access private
     */
    function fetchInto($result, &$arr, $fetchmode, $rownum=null)
    {
        if ($rownum !== null) {
            if (!@mssql_data_seek($result, $rownum)) {
                return null;
            }
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $arr = @mssql_fetch_array($result, MSSQL_ASSOC);
            if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE && $arr) {
                $arr = array_change_key_case($arr, CASE_LOWER);
            }
        } else {
            $arr = @mssql_fetch_row($result);
        }
        if (!$arr) {
            /* This throws informative error messages,
               don't use it for now
            if ($msg = mssql_get_last_message()) {
                return $this->raiseError($msg);
            }
            */
            return null;
        }
        if ($this->options['portability'] & DB_PORTABILITY_RTRIM) {
            $this->_rtrimArrayValues($arr);
        }
        return DB_OK;
    }

    // }}}
    // {{{ freeResult()

    function freeResult($result)
    {
        return @mssql_free_result($result);
    }

    // }}}
    // {{{ numCols()

    function numCols($result)
    {
        $cols = @mssql_num_fields($result);
        if (!$cols) {
            return $this->mssqlRaiseError();
        }
        return $cols;
    }

    // }}}
    // {{{ numRows()

    function numRows($result)
    {
        $rows = @mssql_num_rows($result);
        if ($rows === false) {
            return $this->mssqlRaiseError();
        }
        return $rows;
    }

    // }}}
    // {{{ autoCommit()

    /**
     * Enable/disable automatic commits
     */
    function autoCommit($onoff = false)
    {
        // XXX if $this->transaction_opcount > 0, we should probably
        // issue a warning here.
        $this->autocommit = $onoff ? true : false;
        return DB_OK;
    }

    // }}}
    // {{{ commit()

    /**
     * Commit the current transaction.
     */
    function commit()
    {
        if ($this->transaction_opcount > 0) {
            if (!@mssql_select_db($this->_db, $this->connection)) {
                return $this->mssqlRaiseError(DB_ERROR_NODBSELECTED);
            }
            $result = @mssql_query('COMMIT TRAN', $this->connection);
            $this->transaction_opcount = 0;
            if (!$result) {
                return $this->mssqlRaiseError();
            }
        }
        return DB_OK;
    }

    // }}}
    // {{{ rollback()

    /**
     * Roll back (undo) the current transaction.
     */
    function rollback()
    {
        if ($this->transaction_opcount > 0) {
            if (!@mssql_select_db($this->_db, $this->connection)) {
                return $this->mssqlRaiseError(DB_ERROR_NODBSELECTED);
            }
            $result = @mssql_query('ROLLBACK TRAN', $this->connection);
            $this->transaction_opcount = 0;
            if (!$result) {
                return $this->mssqlRaiseError();
            }
        }
        return DB_OK;
    }

    // }}}
    // {{{ affectedRows()

    /**
     * Gets the number of rows affected by the last query.
     * if the last query was a select, returns 0.
     *
     * @return number of rows affected by the last query or DB_ERROR
     */
    function affectedRows()
    {
        if (DB::isManip($this->last_query)) {
            $res = @mssql_query('select @@rowcount', $this->connection);
            if (!$res) {
                return $this->mssqlRaiseError();
            }
            $ar = @mssql_fetch_row($res);
            if (!$ar) {
                $result = 0;
            } else {
                @mssql_free_result($res);
                $result = $ar[0];
            }
        } else {
            $result = 0;
        }
        return $result;
    }

    // }}}
    // {{{ nextId()

    /**
     * Returns the next free id in a sequence
     *
     * @param string  $seq_name  name of the sequence
     * @param boolean $ondemand  when true, the seqence is automatically
     *                           created if it does not exist
     *
     * @return int  the next id number in the sequence.  DB_Error if problem.
     *
     * @internal
     * @see DB_common::nextID()
     * @access public
     */
    function nextId($seq_name, $ondemand = true)
    {
        $seqname = $this->getSequenceName($seq_name);
        if (!@mssql_select_db($this->_db, $this->connection)) {
            return $this->mssqlRaiseError(DB_ERROR_NODBSELECTED);
        }
        $repeat = 0;
        do {
            $this->pushErrorHandling(PEAR_ERROR_RETURN);
            $result = $this->query("INSERT INTO $seqname (vapor) VALUES (0)");
            $this->popErrorHandling();
            if ($ondemand && DB::isError($result) &&
                ($result->getCode() == DB_ERROR || $result->getCode() == DB_ERROR_NOSUCHTABLE))
            {
                $repeat = 1;
                $result = $this->createSequence($seq_name);
                if (DB::isError($result)) {
                    return $this->raiseError($result);
                }
            } elseif (!DB::isError($result)) {
                $result =& $this->query("SELECT @@IDENTITY FROM $seqname");
                $repeat = 0;
            } else {
                $repeat = false;
            }
        } while ($repeat);
        if (DB::isError($result)) {
            return $this->raiseError($result);
        }
        $result = $result->fetchRow(DB_FETCHMODE_ORDERED);
        return $result[0];
    }

    /**
     * Creates a new sequence
     *
     * @param string $seq_name  name of the new sequence
     *
     * @return int  DB_OK on success.  A DB_Error object is returned if
     *              problems arise.
     *
     * @internal
     * @see DB_common::createSequence()
     * @access public
     */
    function createSequence($seq_name)
    {
        $seqname = $this->getSequenceName($seq_name);
        return $this->query("CREATE TABLE $seqname ".
                            '([id] [int] IDENTITY (1, 1) NOT NULL ,' .
                            '[vapor] [int] NULL)');
    }

    // }}}
    // {{{ dropSequence()

    /**
     * Deletes a sequence
     *
     * @param string $seq_name  name of the sequence to be deleted
     *
     * @return int  DB_OK on success.  DB_Error if problems.
     *
     * @internal
     * @see DB_common::dropSequence()
     * @access public
     */
    function dropSequence($seq_name)
    {
        $seqname = $this->getSequenceName($seq_name);
        return $this->query("DROP TABLE $seqname");
    }

    // }}}
    // {{{ errorNative()

    /**
     * Determine MS SQL Server error code by querying @@ERROR.
     *
     * @return mixed  mssql's native error code or DB_ERROR if unknown.
     */
    function errorNative()
    {
        $res = mssql_query('select @@ERROR as ErrorCode', $this->connection);
        if (!$res) {
            return DB_ERROR;
        }
        $row = mssql_fetch_row($res);
        return $row[0];
    }

    // }}}
    // {{{ errorCode()

    /**
     * Determine PEAR::DB error code from mssql's native codes.
     *
     * If <var>$nativecode</var> isn't known yet, it will be looked up.
     *
     * @param  mixed  $nativecode  mssql error code, if known
     * @return integer  an error number from a DB error constant
     * @see errorNative()
     */
    function errorCode($nativecode = null)
    {
        if (!$nativecode) {
            $nativecode = $this->errorNative();
        }
        if (isset($this->errorcode_map[$nativecode])) {
            return $this->errorcode_map[$nativecode];
        } else {
            return DB_ERROR;
        }
    }

    // }}}
    // {{{ mssqlRaiseError()

    /**
     * Gather information about an error, then use that info to create a
     * DB error object and finally return that object.
     *
     * @param  integer  $code  PEAR error number (usually a DB constant) if
     *                         manually raising an error
     * @return object  DB error object
     * @see errorCode()
     * @see errorNative()
     * @see DB_common::raiseError()
     */
    function mssqlRaiseError($code = null)
    {
        $message = mssql_get_last_message();
        if (!$code) {
            $code = $this->errorNative();
        }
        return $this->raiseError($this->errorCode($code), null, null, null,
                                 "$code - $message");
    }

    // }}}
    // {{{ tableInfo()

    /**
     * Returns information about a table or a result set.
     *
     * NOTE: only supports 'table' and 'flags' if <var>$result</var>
     * is a table name.
     *
     * @param object|string  $result  DB_result object from a query or a
     *                                string containing the name of a table
     * @param int            $mode    a valid tableInfo mode
     * @return array  an associative array with the information requested
     *                or an error object if something is wrong
     * @access public
     * @internal
     * @see DB_common::tableInfo()
     */
    function tableInfo($result, $mode = null)
    {
        if (isset($result->result)) {
            /*
             * Probably received a result object.
             * Extract the result resource identifier.
             */
            $id = $result->result;
            $got_string = false;
        } elseif (is_string($result)) {
            /*
             * Probably received a table name.
             * Create a result resource identifier.
             */
            if (!@mssql_select_db($this->_db, $this->connection)) {
                return $this->mssqlRaiseError(DB_ERROR_NODBSELECTED);
            }
            $id = @mssql_query("SELECT * FROM $result WHERE 1=0",
                               $this->connection);
            $got_string = true;
        } else {
            /*
             * Probably received a result resource identifier.
             * Copy it.
             * Depricated.  Here for compatibility only.
             */
            $id = $result;
            $got_string = false;
        }

        if (!is_resource($id)) {
            return $this->mssqlRaiseError(DB_ERROR_NEED_MORE_DATA);
        }

        if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE) {
            $case_func = 'strtolower';
        } else {
            $case_func = 'strval';
        }

        $count = @mssql_num_fields($id);

        // made this IF due to performance (one if is faster than $count if's)
        if (!$mode) {
            for ($i=0; $i<$count; $i++) {
                $res[$i]['table'] = $got_string ? $case_func($result) : '';
                $res[$i]['name']  = $case_func(@mssql_field_name($id, $i));
                $res[$i]['type']  = @mssql_field_type($id, $i);
                $res[$i]['len']   = @mssql_field_length($id, $i);
                // We only support flags for tables
                $res[$i]['flags'] = $got_string ? $this->_mssql_field_flags($result, $res[$i]['name']) : '';
            }

        } else { // full
            $res['num_fields']= $count;

            for ($i=0; $i<$count; $i++) {
                $res[$i]['table'] = $got_string ? $case_func($result) : '';
                $res[$i]['name']  = $case_func(@mssql_field_name($id, $i));
                $res[$i]['type']  = @mssql_field_type($id, $i);
                $res[$i]['len']   = @mssql_field_length($id, $i);
                // We only support flags for tables
                $res[$i]['flags'] = $got_string ? $this->_mssql_field_flags($result, $res[$i]['name']) : '';

                if ($mode & DB_TABLEINFO_ORDER) {
                    $res['order'][$res[$i]['name']] = $i;
                }
                if ($mode & DB_TABLEINFO_ORDERTABLE) {
                    $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                }
            }
        }

        // free the result only if we were called on a table
        if ($got_string) {
            @mssql_free_result($id);
        }
        return $res;
    }

    // }}}
    // {{{ getSpecialQuery()

    /**
     * Returns the query needed to get some backend info
     * @param string $type What kind of info you want to retrieve
     * @return string The SQL query string
     */
    function getSpecialQuery($type)
    {
        switch ($type) {
            case 'tables':
                return "select name from sysobjects where type = 'U' order by name";
            case 'views':
                return "select name from sysobjects where type = 'V'";
            default:
                return null;
        }
    }

    // }}}
    // {{{ _mssql_field_flags()

    /**
     * Get the flags for a field, currently supports "not_null", "primary_key",
     * "auto_increment" (mssql identity), "timestamp" (mssql timestamp),
     * "unique_key" (mssql unique index, unique check or primary_key) and
     * "multiple_key" (multikey index)
     *
     * mssql timestamp is NOT similar to the mysql timestamp so this is maybe
     * not useful at all - is the behaviour of mysql_field_flags that primary
     * keys are alway unique? is the interpretation of multiple_key correct?
     *
     * @param string The table name
     * @param string The field
     * @author Joern Barthel <j_barthel@web.de>
     * @access private
     */
    function _mssql_field_flags($table, $column)
    {
        static $tableName = null;
        static $flags = array();

        if ($table != $tableName) {

            $flags = array();
            $tableName = $table;

            // get unique and primary keys
            $res = $this->getAll("EXEC SP_HELPINDEX[$table]", DB_FETCHMODE_ASSOC);

            foreach ($res as $val) {
                $keys = explode(', ', $val['index_keys']);

                if (sizeof($keys) > 1) {
                    foreach ($keys as $key) {
                        $this->_add_flag($flags[$key], 'multiple_key');
                    }
                }

                if (strpos($val['index_description'], 'primary key')) {
                    foreach ($keys as $key) {
                        $this->_add_flag($flags[$key], 'primary_key');
                    }
                } elseif (strpos($val['index_description'], 'unique')) {
                    foreach ($keys as $key) {
                        $this->_add_flag($flags[$key], 'unique_key');
                    }
                }
            }

            // get auto_increment, not_null and timestamp
            $res = $this->getAll("EXEC SP_COLUMNS[$table]", DB_FETCHMODE_ASSOC);

            foreach ($res as $val) {
                $val = array_change_key_case($val, CASE_LOWER);
                if ($val['nullable'] == '0') {
                    $this->_add_flag($flags[$val['column_name']], 'not_null');
                }
                if (strpos($val['type_name'], 'identity')) {
                    $this->_add_flag($flags[$val['column_name']], 'auto_increment');
                }
                if (strpos($val['type_name'], 'timestamp')) {
                    $this->_add_flag($flags[$val['column_name']], 'timestamp');
                }
            }
        }

        if (array_key_exists($column, $flags)) {
            return(implode(' ', $flags[$column]));
        }
        return '';
    }

    // }}}
    // {{{ _add_flag()

    /**
     * Adds a string to the flags array if the flag is not yet in there
     * - if there is no flag present the array is created.
     *
     * @param reference  Reference to the flag-array
     * @param value      The flag value
     * @access private
     * @author Joern Barthel <j_barthel@web.de>
     */
    function _add_flag (&$array, $value)
    {
        if (!is_array($array)) {
            $array = array($value);
        } elseif (!in_array($value, $array)) {
            array_push($array, $value);
        }
    }

    // }}}
    // {{{ quoteIdentifier()

    /**
     * Quote a string so it can be safely used as a table / column name
     *
     * Quoting style depends on which database driver is being used.
     *
     * @param string $str  identifier name to be quoted
     *
     * @return string  quoted identifier string
     *
     * @since 1.6.0
     * @access public
     */
    function quoteIdentifier($str)
    {
        return '[' . str_replace(']', ']]', $str) . ']';
    }

    // }}}
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */

?>
