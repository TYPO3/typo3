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
// | Author: James L. Pine <jlp@valinux.com>                              |
// | Maintainer: Daniel Convissor <danielc@php.net>                       |
// +----------------------------------------------------------------------+
//
// $Id$


// be aware...  OCIError() only appears to return anything when given a
// statement, so functions return the generic DB_ERROR instead of more
// useful errors that have to do with feedback from the database.


require_once 'DB/common.php';

/**
 * Database independent query interface definition for PHP's Oracle 8
 * call-interface extension.
 *
 * Definitely works with versions 8 and 9 of Oracle.
 *
 * @package  DB
 * @version  $Id$
 * @category Database
 * @author   James L. Pine <jlp@valinux.com>
 */
class DB_oci8 extends DB_common
{
    // {{{ properties

    var $connection;
    var $phptype, $dbsyntax;
    var $manip_query = array();
    var $prepare_types = array();
    var $autoCommit = 1;
    var $last_stmt = false;

    // }}}
    // {{{ constructor

    function DB_oci8()
    {
        $this->DB_common();
        $this->phptype = 'oci8';
        $this->dbsyntax = 'oci8';
        $this->features = array(
            'prepare' => false,
            'pconnect' => true,
            'transactions' => true,
            'limit' => 'alter'
        );
        $this->errorcode_map = array(
            1 => DB_ERROR_CONSTRAINT,
            900 => DB_ERROR_SYNTAX,
            904 => DB_ERROR_NOSUCHFIELD,
            921 => DB_ERROR_SYNTAX,
            923 => DB_ERROR_SYNTAX,
            942 => DB_ERROR_NOSUCHTABLE,
            955 => DB_ERROR_ALREADY_EXISTS,
            1476 => DB_ERROR_DIVZERO,
            1722 => DB_ERROR_INVALID_NUMBER,
            2289 => DB_ERROR_NOSUCHTABLE,
            2291 => DB_ERROR_CONSTRAINT,
            2449 => DB_ERROR_CONSTRAINT,
        );
    }

    // }}}
    // {{{ connect()

    /**
     * Connect to a database and log in as the specified user.
     *
     * @param $dsn the data source name (see DB::parseDSN for syntax)
     * @param $persistent (optional) whether the connection should
     *        be persistent
     *
     * @return int DB_OK on success, a DB error code on failure
     */
    function connect($dsninfo, $persistent = false)
    {
        if (!DB::assertExtension('oci8')) {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }
        $this->dsn = $dsninfo;

        $connect_function = $persistent ? 'OCIPLogon' : 'OCILogon';

        if ($dsninfo['hostspec']) {
            $conn = @$connect_function($dsninfo['username'],
                                       $dsninfo['password'],
                                       $dsninfo['hostspec']);
        } elseif ($dsninfo['username'] || $dsninfo['password']) {
            $conn = @$connect_function($dsninfo['username'],
                                       $dsninfo['password']);
        } else {
            $conn = false;
        }
        if ($conn == false) {
            $error = OCIError();
            $error = (is_array($error)) ? $error['message'] : null;
            return $this->raiseError(DB_ERROR_CONNECT_FAILED, null, null,
                                     null, $error);
        }
        $this->connection = $conn;
        return DB_OK;
    }

    // }}}
    // {{{ disconnect()

    /**
     * Log out and disconnect from the database.
     *
     * @return bool TRUE on success, FALSE if not connected.
     */
    function disconnect()
    {
        $ret = @OCILogOff($this->connection);
        $this->connection = null;
        return $ret;
    }

    // }}}
    // {{{ simpleQuery()

    /**
     * Send a query to oracle and return the results as an oci8 resource
     * identifier.
     *
     * @param $query the SQL query
     *
     * @return int returns a valid oci8 result for successful SELECT
     * queries, DB_OK for other successful queries.  A DB error code
     * is returned on failure.
     */
    function simpleQuery($query)
    {
        $this->last_query = $query;
        $query = $this->modifyQuery($query);
        $result = @OCIParse($this->connection, $query);
        if (!$result) {
            return $this->oci8RaiseError();
        }
        if ($this->autoCommit) {
            $success = @OCIExecute($result,OCI_COMMIT_ON_SUCCESS);
        } else {
            $success = @OCIExecute($result,OCI_DEFAULT);
        }
        if (!$success) {
            return $this->oci8RaiseError($result);
        }
        $this->last_stmt=$result;
        // Determine which queries that should return data, and which
        // should return an error code only.
        return DB::isManip($query) ? DB_OK : $result;
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal oracle result pointer to the next available result
     *
     * @param a valid oci8 result resource
     *
     * @access public
     *
     * @return true if a result is available otherwise return false
     */
    function nextResult($result)
    {
        return false;
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
        if ($rownum !== NULL) {
            return $this->raiseError(DB_ERROR_NOT_CAPABLE);
        }
        if ($fetchmode & DB_FETCHMODE_ASSOC) {
            $moredata = @OCIFetchInto($result,$arr,OCI_ASSOC+OCI_RETURN_NULLS+OCI_RETURN_LOBS);
            if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE &&
                $moredata)
            {
                $arr = array_change_key_case($arr, CASE_LOWER);
            }
        } else {
            $moredata = OCIFetchInto($result,$arr,OCI_RETURN_NULLS+OCI_RETURN_LOBS);
        }
        if (!$moredata) {
            return NULL;
        }
        if ($this->options['portability'] & DB_PORTABILITY_RTRIM) {
            $this->_rtrimArrayValues($arr);
        }
        return DB_OK;
    }

    // }}}
    // {{{ freeResult()

    /**
     * Free the internal resources associated with $result.
     *
     * @param $result oci8 result identifier
     *
     * @return bool TRUE on success, FALSE if $result is invalid
     */
    function freeResult($result)
    {
        return @OCIFreeStatement($result);
    }

    /**
     * Free the internal resources associated with a prepared query.
     *
     * @param $stmt oci8 statement identifier
     *
     * @return bool TRUE on success, FALSE if $result is invalid
     */
    function freePrepared($stmt)
    {
        if (isset($this->prepare_types[(int)$stmt])) {
            unset($this->prepare_types[(int)$stmt]);
            unset($this->manip_query[(int)$stmt]);
        } else {
            return false;
        }
        return true;
    }

    // }}}
    // {{{ numRows()

    function numRows($result)
    {
        // emulate numRows for Oracle.  yuck.
        if ($this->options['portability'] & DB_PORTABILITY_NUMROWS &&
            $result === $this->last_stmt)
        {
            $countquery = 'SELECT COUNT(*) FROM ('.$this->last_query.')';
            $save_query = $this->last_query;
            $save_stmt = $this->last_stmt;
            $count =& $this->query($countquery);
            if (DB::isError($count) ||
                DB::isError($row = $count->fetchRow(DB_FETCHMODE_ORDERED)))
            {
                $this->last_query = $save_query;
                $this->last_stmt = $save_stmt;
                return $this->raiseError(DB_ERROR_NOT_CAPABLE);
            }
            return $row[0];
        }
        return $this->raiseError(DB_ERROR_NOT_CAPABLE);
    }

    // }}}
    // {{{ numCols()

    /**
     * Get the number of columns in a result set.
     *
     * @param $result oci8 result identifier
     *
     * @return int the number of columns per row in $result
     */
    function numCols($result)
    {
        $cols = @OCINumCols($result);
        if (!$cols) {
            return $this->oci8RaiseError($result);
        }
        return $cols;
    }

    // }}}
    // {{{ errorNative()

    /**
     * Get the native error code of the last error (if any) that occured
     * on the current connection.  This does not work, as OCIError does
     * not work unless given a statement.  If OCIError does return
     * something, so will this.
     *
     * @return int native oci8 error code
     */
    function errorNative()
    {
        if (is_resource($this->last_stmt)) {
            $error = @OCIError($this->last_stmt);
        } else {
            $error = @OCIError($this->connection);
        }
        if (is_array($error)) {
            return $error['code'];
        }
        return false;
    }

    // }}}
    // {{{ prepare()

    /**
     * Prepares a query for multiple execution with execute().
     *
     * With oci8, this is emulated.
     *
     * prepare() requires a generic query as string like <code>
     *    INSERT INTO numbers VALUES (?, ?, ?)
     * </code>.  The <kbd>?</kbd> characters are placeholders.
     *
     * Three types of placeholders can be used:
     *   + <kbd>?</kbd>  a quoted scalar value, i.e. strings, integers
     *   + <kbd>!</kbd>  value is inserted 'as is'
     *   + <kbd>&</kbd>  requires a file name.  The file's contents get
     *                     inserted into the query (i.e. saving binary
     *                     data in a db)
     *
     * Use backslashes to escape placeholder characters if you don't want
     * them to be interpreted as placeholders.  Example: <code>
     *    "UPDATE foo SET col=? WHERE col='over \& under'"
     * </code>
     *
     * @param string $query query to be prepared
     * @return mixed DB statement resource on success. DB_Error on failure.
     */
    function prepare($query)
    {
        $tokens   = preg_split('/((?<!\\\)[&?!])/', $query, -1,
                               PREG_SPLIT_DELIM_CAPTURE);
        $binds    = count($tokens) - 1;
        $token    = 0;
        $types    = array();
        $newquery = '';

        foreach ($tokens as $key => $val) {
            switch ($val) {
                case '?':
                    $types[$token++] = DB_PARAM_SCALAR;
                    unset($tokens[$key]);
                    break;
                case '&':
                    $types[$token++] = DB_PARAM_OPAQUE;
                    unset($tokens[$key]);
                    break;
                case '!':
                    $types[$token++] = DB_PARAM_MISC;
                    unset($tokens[$key]);
                    break;
                default:
                    $tokens[$key] = preg_replace('/\\\([&?!])/', "\\1", $val);
                    if ($key != $binds) {
                        $newquery .= $tokens[$key] . ':bind' . $token;
                    } else {
                        $newquery .= $tokens[$key];
                    }
            }
        }

        $this->last_query = $query;
        $newquery = $this->modifyQuery($newquery);
        if (!$stmt = @OCIParse($this->connection, $newquery)) {
            return $this->oci8RaiseError();
        }
        $this->prepare_types[$stmt] = $types;
        $this->manip_query[(int)$stmt] = DB::isManip($query);
        return $stmt;
    }

    // }}}
    // {{{ execute()

    /**
     * Executes a DB statement prepared with prepare().
     *
     * @param resource  $stmt  a DB statement resource returned from prepare()
     * @param mixed  $data  array, string or numeric data to be used in
     *                      execution of the statement.  Quantity of items
     *                      passed must match quantity of placeholders in
     *                      query:  meaning 1 for non-array items or the
     *                      quantity of elements in the array.
     * @return int returns an oci8 result resource for successful
     * SELECT queries, DB_OK for other successful queries.  A DB error
     * code is returned on failure.
     * @see DB_oci::prepare()
     */
    function &execute($stmt, $data = array())
    {
        if (!is_array($data)) {
            $data = array($data);
        }

        $types =& $this->prepare_types[$stmt];
        if (count($types) != count($data)) {
            $tmp =& $this->raiseError(DB_ERROR_MISMATCH);
            return $tmp;
        }

        $i = 0;
        foreach ($data as $key => $value) {
            if ($types[$i] == DB_PARAM_MISC) {
                /*
                 * Oracle doesn't seem to have the ability to pass a
                 * parameter along unchanged, so strip off quotes from start
                 * and end, plus turn two single quotes to one single quote,
                 * in order to avoid the quotes getting escaped by
                 * Oracle and ending up in the database.
                 */
                $data[$key] = preg_replace("/^'(.*)'$/", "\\1", $data[$key]);
                $data[$key] = str_replace("''", "'", $data[$key]);
            } elseif ($types[$i] == DB_PARAM_OPAQUE) {
                $fp = @fopen($data[$key], 'rb');
                if (!$fp) {
                    $tmp =& $this->raiseError(DB_ERROR_ACCESS_VIOLATION);
                    return $tmp;
                }
                $data[$key] = fread($fp, filesize($data[$key]));
                fclose($fp);
            }
            if (!@OCIBindByName($stmt, ':bind' . $i, $data[$key], -1)) {
                $tmp = $this->oci8RaiseError($stmt);
                return $tmp;
            }
            $i++;
        }
        if ($this->autoCommit) {
            $success = @OCIExecute($stmt, OCI_COMMIT_ON_SUCCESS);
        } else {
            $success = @OCIExecute($stmt, OCI_DEFAULT);
        }
        if (!$success) {
            $tmp = $this->oci8RaiseError($stmt);
            return $tmp;
        }
        $this->last_stmt = $stmt;
        if ($this->manip_query[(int)$stmt]) {
            $tmp = DB_OK;
        } else {
            $tmp =& new DB_result($this, $stmt);
        }
        return $tmp;
    }

    // }}}
    // {{{ autoCommit()

    /**
     * Enable/disable automatic commits
     *
     * @param $onoff true/false whether to autocommit
     */
    function autoCommit($onoff = false)
    {
        $this->autoCommit = (bool)$onoff;;
        return DB_OK;
    }

    // }}}
    // {{{ commit()

    /**
     * Commit transactions on the current connection
     *
     * @return DB_ERROR or DB_OK
     */
    function commit()
    {
        $result = @OCICommit($this->connection);
        if (!$result) {
            return $this->oci8RaiseError();
        }
        return DB_OK;
    }

    // }}}
    // {{{ rollback()

    /**
     * Roll back all uncommitted transactions on the current connection.
     *
     * @return DB_ERROR or DB_OK
     */
    function rollback()
    {
        $result = @OCIRollback($this->connection);
        if (!$result) {
            return $this->oci8RaiseError();
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
        if ($this->last_stmt === false) {
            return $this->oci8RaiseError();
        }
        $result = @OCIRowCount($this->last_stmt);
        if ($result === false) {
            return $this->oci8RaiseError($this->last_stmt);
        }
        return $result;
    }

    // }}}
    // {{{ modifyQuery()

    function modifyQuery($query)
    {
        // "SELECT 2+2" must be "SELECT 2+2 FROM dual" in Oracle
        if (preg_match('/^\s*SELECT/i', $query) &&
            !preg_match('/\sFROM\s/i', $query)) {
            $query .= ' FROM dual';
        }
        return $query;
    }

    // }}}
    // {{{ modifyLimitQuery()

    /**
     * Emulate the row limit support altering the query
     *
     * @param string $query The query to treat
     * @param int    $from  The row to start to fetch from
     * @param int    $count The offset
     * @return string The modified query
     *
     * @author Tomas V.V.Cox <cox@idecnet.com>
     */
    function modifyLimitQuery($query, $from, $count)
    {
        // Let Oracle return the name of the columns instead of
        // coding a "home" SQL parser
        $q_fields = "SELECT * FROM ($query) WHERE NULL = NULL";
        if (!$result = @OCIParse($this->connection, $q_fields)) {
            $this->last_query = $q_fields;
            return $this->oci8RaiseError();
        }
        if (!@OCIExecute($result, OCI_DEFAULT)) {
            $this->last_query = $q_fields;
            return $this->oci8RaiseError($result);
        }
        $ncols = OCINumCols($result);
        $cols  = array();
        for ( $i = 1; $i <= $ncols; $i++ ) {
            $cols[] = '"' . OCIColumnName($result, $i) . '"';
        }
        $fields = implode(', ', $cols);
        // XXX Test that (tip by John Lim)
        //if(preg_match('/^\s*SELECT\s+/is', $query, $match)) {
        //    // Introduce the FIRST_ROWS Oracle query optimizer
        //    $query = substr($query, strlen($match[0]), strlen($query));
        //    $query = "SELECT /* +FIRST_ROWS */ " . $query;
        //}

        // Construct the query
        // more at: http://marc.theaimsgroup.com/?l=php-db&m=99831958101212&w=2
        // Perhaps this could be optimized with the use of Unions
        $query = "SELECT $fields FROM".
                 "  (SELECT rownum as linenum, $fields FROM".
                 "      ($query)".
                 '  WHERE rownum <= '. ($from + $count) .
                 ') WHERE linenum >= ' . ++$from;
        return $query;
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
        $repeat = 0;
        do {
            $this->expectError(DB_ERROR_NOSUCHTABLE);
            $result =& $this->query("SELECT ${seqname}.nextval FROM dual");
            $this->popExpect();
            if ($ondemand && DB::isError($result) &&
                $result->getCode() == DB_ERROR_NOSUCHTABLE) {
                $repeat = 1;
                $result = $this->createSequence($seq_name);
                if (DB::isError($result)) {
                    return $this->raiseError($result);
                }
            } else {
                $repeat = 0;
            }
        } while ($repeat);
        if (DB::isError($result)) {
            return $this->raiseError($result);
        }
        $arr = $result->fetchRow(DB_FETCHMODE_ORDERED);
        return $arr[0];
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
        return $this->query("CREATE SEQUENCE ${seqname}");
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
        return $this->query("DROP SEQUENCE ${seqname}");
    }

    // }}}
    // {{{ oci8RaiseError()

    /**
     * Gather information about an error, then use that info to create a
     * DB error object and finally return that object.
     *
     * @param  integer  $errno  PEAR error number (usually a DB constant) if
     *                          manually raising an error
     * @return object  DB error object
     * @see DB_common::errorCode()
     * @see DB_common::raiseError()
     */
    function oci8RaiseError($errno = null)
    {
        if ($errno === null) {
            $error = @OCIError($this->connection);
            return $this->raiseError($this->errorCode($error['code']),
                                     null, null, null, $error['message']);
        } elseif (is_resource($errno)) {
            $error = @OCIError($errno);
            return $this->raiseError($this->errorCode($error['code']),
                                     null, null, null, $error['message']);
        }
        return $this->raiseError($this->errorCode($errno));
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
                return 'SELECT table_name FROM user_tables';
            default:
                return null;
        }
    }

    // }}}
    // {{{ tableInfo()

    /**
     * Returns information about a table or a result set.
     *
     * NOTE: only supports 'table' and 'flags' if <var>$result</var>
     * is a table name.
     *
     * NOTE: flags won't contain index information.
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
        if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE) {
            $case_func = 'strtolower';
        } else {
            $case_func = 'strval';
        }

        if (is_string($result)) {
            /*
             * Probably received a table name.
             * Create a result resource identifier.
             */
            $result = strtoupper($result);
            $q_fields = 'SELECT column_name, data_type, data_length, '
                        . 'nullable '
                        . 'FROM user_tab_columns '
                        . "WHERE table_name='$result' ORDER BY column_id";

            $this->last_query = $q_fields;

            if (!$stmt = @OCIParse($this->connection, $q_fields)) {
                return $this->oci8RaiseError(DB_ERROR_NEED_MORE_DATA);
            }
            if (!@OCIExecute($stmt, OCI_DEFAULT)) {
                return $this->oci8RaiseError($stmt);
            }

            $i = 0;
            while (@OCIFetch($stmt)) {
                $res[$i]['table'] = $case_func($result);
                $res[$i]['name']  = $case_func(@OCIResult($stmt, 1));
                $res[$i]['type']  = @OCIResult($stmt, 2);
                $res[$i]['len']   = @OCIResult($stmt, 3);
                $res[$i]['flags'] = (@OCIResult($stmt, 4) == 'N') ? 'not_null' : '';

                if ($mode & DB_TABLEINFO_ORDER) {
                    $res['order'][$res[$i]['name']] = $i;
                }
                if ($mode & DB_TABLEINFO_ORDERTABLE) {
                    $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                }
                $i++;
            }

            $res['num_fields'] = $i;
            @OCIFreeStatement($stmt);

        } else {
            if (isset($result->result)) {
                /*
                 * Probably received a result object.
                 * Extract the result resource identifier.
                 */
                $result = $result->result;
            } else {
                /*
                 * ELSE, probably received a result resource identifier.
                 * Depricated.  Here for compatibility only.
                 */
            }

            if ($result === $this->last_stmt) {
                $count = @OCINumCols($result);

                for ($i=0; $i<$count; $i++) {
                    $res[$i]['table'] = '';
                    $res[$i]['name']  = $case_func(@OCIColumnName($result, $i+1));
                    $res[$i]['type']  = @OCIColumnType($result, $i+1);
                    $res[$i]['len']   = @OCIColumnSize($result, $i+1);
                    $res[$i]['flags'] = '';

                    if ($mode & DB_TABLEINFO_ORDER) {
                        $res['order'][$res[$i]['name']] = $i;
                    }
                    if ($mode & DB_TABLEINFO_ORDERTABLE) {
                        $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                    }
                }

                $res['num_fields'] = $count;

            } else {
                return $this->raiseError(DB_ERROR_NOT_CAPABLE);
            }
        }
        return $res;
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
