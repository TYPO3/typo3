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
// | Author: Stig Bakken <ssb@php.net>                                    |
// | Maintainer: Daniel Convissor <danielc@php.net>                       |
// +----------------------------------------------------------------------+
//
// $Id$


// XXX legend:
//  More info on ODBC errors could be found here:
//  http://msdn.microsoft.com/library/default.asp?url=/library/en-us/trblsql/tr_err_odbc_5stz.asp
//
// XXX ERRORMSG: The error message from the odbc function should
//                 be registered here.


require_once 'DB/common.php';

/**
 * Database independent query interface definition for PHP's ODBC
 * extension.
 *
 * @package  DB
 * @version  $Id$
 * @category Database
 * @author   Stig Bakken <ssb@php.net>
 */
class DB_odbc extends DB_common
{
    // {{{ properties

    var $connection;
    var $phptype, $dbsyntax;
    var $row = array();

    // }}}
    // {{{ constructor

    function DB_odbc()
    {
        $this->DB_common();
        $this->phptype = 'odbc';
        $this->dbsyntax = 'sql92';
        $this->features = array(
            'prepare' => true,
            'pconnect' => true,
            'transactions' => false,
            'limit' => 'emulate'
        );
        $this->errorcode_map = array(
            '01004' => DB_ERROR_TRUNCATED,
            '07001' => DB_ERROR_MISMATCH,
            '21S01' => DB_ERROR_MISMATCH,
            '21S02' => DB_ERROR_MISMATCH,
            '22003' => DB_ERROR_INVALID_NUMBER,
            '22005' => DB_ERROR_INVALID_NUMBER,
            '22008' => DB_ERROR_INVALID_DATE,
            '22012' => DB_ERROR_DIVZERO,
            '23000' => DB_ERROR_CONSTRAINT,
            '23503' => DB_ERROR_CONSTRAINT,
            '23505' => DB_ERROR_CONSTRAINT,
            '24000' => DB_ERROR_INVALID,
            '34000' => DB_ERROR_INVALID,
            '37000' => DB_ERROR_SYNTAX,
            '42000' => DB_ERROR_SYNTAX,
            '42601' => DB_ERROR_SYNTAX,
            'IM001' => DB_ERROR_UNSUPPORTED,
            'S0000' => DB_ERROR_NOSUCHTABLE,
            'S0001' => DB_ERROR_ALREADY_EXISTS,
            'S0002' => DB_ERROR_NOSUCHTABLE,
            'S0011' => DB_ERROR_ALREADY_EXISTS,
            'S0012' => DB_ERROR_NOT_FOUND,
            'S0021' => DB_ERROR_ALREADY_EXISTS,
            'S0022' => DB_ERROR_NOSUCHFIELD,
            'S1009' => DB_ERROR_INVALID,
            'S1090' => DB_ERROR_INVALID,
            'S1C00' => DB_ERROR_NOT_CAPABLE
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
        if (!DB::assertExtension('odbc')) {
            return $this->raiseError(DB_ERROR_EXTENSION_NOT_FOUND);
        }

        $this->dsn = $dsninfo;
        if ($dsninfo['dbsyntax']) {
            $this->dbsyntax = $dsninfo['dbsyntax'];
        }
        switch ($this->dbsyntax) {
            case 'solid':
                $this->features = array(
                    'prepare' => true,
                    'pconnect' => true,
                    'transactions' => true
                );
                break;
            case 'navision':
                // the Navision driver doesn't support fetch row by number
                $this->features['limit'] = false;
                break;
            case 'access':
                if ($this->options['portability'] & DB_PORTABILITY_ERRORS) {
                    $this->errorcode_map['07001'] = DB_ERROR_NOSUCHFIELD;
                }
                break;
            default:
                break;
        }

        /*
         * This is hear for backwards compatibility.
         * Should have been using 'database' all along, but used hostspec.
         */
        if ($dsninfo['database']) {
            $odbcdsn = $dsninfo['database'];
        } elseif ($dsninfo['hostspec']) {
            $odbcdsn = $dsninfo['hostspec'];
        } else {
            $odbcdsn = 'localhost';
        }

        if ($this->provides('pconnect')) {
            $connect_function = $persistent ? 'odbc_pconnect' : 'odbc_connect';
        } else {
            $connect_function = 'odbc_connect';
        }
        $conn = @$connect_function($odbcdsn, $dsninfo['username'],
                                   $dsninfo['password']);
        if (!is_resource($conn)) {
            return $this->raiseError(DB_ERROR_CONNECT_FAILED, null, null,
                                         null, $this->errorNative());
        }
        $this->connection = $conn;
        return DB_OK;
    }

    // }}}
    // {{{ disconnect()

    function disconnect()
    {
        $err = @odbc_close($this->connection);
        $this->connection = null;
        return $err;
    }

    // }}}
    // {{{ simpleQuery()

    /**
     * Send a query to ODBC and return the results as a ODBC resource
     * identifier.
     *
     * @param $query the SQL query
     *
     * @return int returns a valid ODBC result for successful SELECT
     * queries, DB_OK for other successful queries.  A DB error code
     * is returned on failure.
     */
    function simpleQuery($query)
    {
        $this->last_query = $query;
        $query = $this->modifyQuery($query);
        $result = @odbc_exec($this->connection, $query);
        if (!$result) {
            return $this->odbcRaiseError(); // XXX ERRORMSG
        }
        // Determine which queries that should return data, and which
        // should return an error code only.
        if (DB::isManip($query)) {
            $this->manip_result = $result; // For affectedRows()
            return DB_OK;
        }
        $this->row[(int)$result] = 0;
        $this->manip_result = 0;
        return $result;
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal odbc result pointer to the next available result
     *
     * @param a valid fbsql result resource
     *
     * @access public
     *
     * @return true if a result is available otherwise return false
     */
    function nextResult($result)
    {
        return odbc_next_result($result);
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
        $arr = array();
        if ($rownum !== null) {
            $rownum++; // ODBC first row is 1
            if (version_compare(phpversion(), '4.2.0', 'ge')) {
                $cols = odbc_fetch_into($result, $arr, $rownum);
            } else {
                $cols = odbc_fetch_into($result, $rownum, $arr);
            }
        } else {
            $cols = odbc_fetch_into($result, $arr);
        }

        if (!$cols) {
            /* XXX FIXME: doesn't work with unixODBC and easysoft
                          (get corrupted $errno values)
            if ($errno = odbc_error($this->connection)) {
                return $this->RaiseError($errno);
            }*/
            return null;
        }
        if ($fetchmode !== DB_FETCHMODE_ORDERED) {
            for ($i = 0; $i < count($arr); $i++) {
                $colName = odbc_field_name($result, $i+1);
                $a[$colName] = $arr[$i];
            }
            if ($this->options['portability'] & DB_PORTABILITY_LOWERCASE) {
                $a = array_change_key_case($a, CASE_LOWER);
            }
            $arr = $a;
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
        unset($this->row[(int)$result]);
        return @odbc_free_result($result);
    }

    // }}}
    // {{{ numCols()

    function numCols($result)
    {
        $cols = @odbc_num_fields($result);
        if (!$cols) {
            return $this->odbcRaiseError();
        }
        return $cols;
    }

    // }}}
    // {{{ affectedRows()

    /**
     * Returns the number of rows affected by a manipulative query
     * (INSERT, DELETE, UPDATE)
     * @return mixed int affected rows, 0 when non manip queries or
     *               DB error on error
     */
    function affectedRows()
    {
        if (empty($this->manip_result)) {  // In case of SELECT stms
            return 0;
        }
        $nrows = odbc_num_rows($this->manip_result);
        if ($nrows == -1) {
            return $this->odbcRaiseError();
        }
        return $nrows;
    }

    // }}}
    // {{{ numRows()

    /**
     * ODBC may or may not support counting rows in the result set of
     * SELECTs.
     *
     * @param $result the odbc result resource
     * @return the number of rows, or 0
     */
    function numRows($result)
    {
        $nrows = odbc_num_rows($result);
        if ($nrows == -1) {
            return $this->odbcRaiseError(DB_ERROR_UNSUPPORTED);
        }
        return $nrows;
    }

    // }}}
    // {{{ quoteIdentifier()

    /**
     * Quote a string so it can be safely used as a table / column name
     *
     * Quoting style depends on which dbsyntax was passed in the DSN.
     *
     * Use 'mssql' as the dbsyntax in the DB DSN only if you've unchecked
     * "Use ANSI quoted identifiers" when setting up the ODBC data source.
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
        switch ($this->dsn['dbsyntax']) {
            case 'access':
                return '[' . $str . ']';
            case 'mssql':
            case 'sybase':
                return '[' . str_replace(']', ']]', $str) . ']';
            case 'mysql':
            case 'mysql4':
                return '`' . $str . '`';
            default:
                return '"' . str_replace('"', '""', $str) . '"';
        }
    }

    // }}}
    // {{{ quote()

    /**
     * @deprecated  Deprecated in release 1.6.0
     * @internal
     */
    function quote($str) {
        return $this->quoteSmart($str);
    }

    // }}}
    // {{{ errorNative()

    /**
     * Get the native error code of the last error (if any) that
     * occured on the current connection.
     *
     * @access public
     *
     * @return int ODBC error code
     */
    function errorNative()
    {
        if (!isset($this->connection) || !is_resource($this->connection)) {
            return odbc_error() . ' ' . odbc_errormsg();
        }
        return odbc_error($this->connection) . ' ' . odbc_errormsg($this->connection);
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
            $this->pushErrorHandling(PEAR_ERROR_RETURN);
            $result = $this->query("update ${seqname} set id = id + 1");
            $this->popErrorHandling();
            if ($ondemand && DB::isError($result) &&
                $result->getCode() == DB_ERROR_NOSUCHTABLE) {
                $repeat = 1;
                $this->pushErrorHandling(PEAR_ERROR_RETURN);
                $result = $this->createSequence($seq_name);
                $this->popErrorHandling();
                if (DB::isError($result)) {
                    return $this->raiseError($result);
                }
                $result = $this->query("insert into ${seqname} (id) values(0)");
            } else {
                $repeat = 0;
            }
        } while ($repeat);

        if (DB::isError($result)) {
            return $this->raiseError($result);
        }

        $result = $this->query("select id from ${seqname}");
        if (DB::isError($result)) {
            return $result;
        }

        $row = $result->fetchRow(DB_FETCHMODE_ORDERED);
        if (DB::isError($row || !$row)) {
            return $row;
        }

        return $row[0];
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
        return $this->query("CREATE TABLE ${seqname} ".
                            '(id integer NOT NULL,'.
                            ' PRIMARY KEY(id))');
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
        return $this->query("DROP TABLE ${seqname}");
    }

    // }}}
    // {{{ autoCommit()

    function autoCommit($onoff = false)
    {
        if (!@odbc_autocommit($this->connection, $onoff)) {
            return $this->odbcRaiseError();
        }
        return DB_OK;
    }

    // }}}
    // {{{ commit()

    function commit()
    {
        if (!@odbc_commit($this->connection)) {
            return $this->odbcRaiseError();
        }
        return DB_OK;
    }

    // }}}
    // {{{ rollback()

    function rollback()
    {
        if (!@odbc_rollback($this->connection)) {
            return $this->odbcRaiseError();
        }
        return DB_OK;
    }

    // }}}
    // {{{ odbcRaiseError()

    /**
     * Gather information about an error, then use that info to create a
     * DB error object and finally return that object.
     *
     * @param  integer  $errno  PEAR error number (usually a DB constant) if
     *                          manually raising an error
     * @return object  DB error object
     * @see errorNative()
     * @see DB_common::errorCode()
     * @see DB_common::raiseError()
     */
    function odbcRaiseError($errno = null)
    {
        if ($errno === null) {
            $errno = $this->errorCode(odbc_error($this->connection));
        }
        return $this->raiseError($errno, null, null, null,
                        $this->errorNative());
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
